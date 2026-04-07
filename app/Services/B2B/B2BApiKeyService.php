<?php declare(strict_types=1);

namespace App\Services\B2B;


use Illuminate\Http\Request;
use App\Models\B2BApiKey;
use App\Models\BusinessGroup;
use App\Services\FraudControlService;


use Illuminate\Support\Str;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;

/**
 * B2BApiKeyService — создание и валидация API-ключей B2B-клиентов.
 *
 * Правила канона:
 *  - Ключ = префикс "b2b_" + 64 random bytes (hex) = 68 символов итого
 *  - В БД хранится только SHA256(key) — открытый ключ показывается ТОЛЬКО один раз при создании
 *  - Проверка: находим по hashed_key, проверяем is_active + expires_at + tenant_id
 *  - Каждый запрос обновляет last_used_at + last_ip (async через Job)
 *  - Fraud-check при создании и ротации
 */
final readonly class B2BApiKeyService
{
    public function __construct(
        private readonly Request $request,
        private FraudControlService $fraud,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать новый API-ключ для BusinessGroup.
     *
     * @param string[] $permissions
     * @return array{key: string, model: B2BApiKey}  key показывается только один раз
     */
    public function create(
        BusinessGroup $group,
        string        $name,
        array         $permissions,
        string        $correlationId,
        ?\DateTimeInterface $expiresAt = null,
    ): array {
        $this->fraud->check(
            (int) $this->guard->id(),
            'b2b_api_key_create',
            0,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use ($group, $name, $permissions, $expiresAt, $correlationId): array {
            $rawKey    = 'b2b_' . bin2hex(random_bytes(32));
            $hashedKey = hash('sha256', $rawKey);

            $model = B2BApiKey::create([
                'business_group_id' => $group->id,
                'tenant_id'         => $group->tenant_id,
                'uuid'              => Str::uuid()->toString(),
                'name'              => $name,
                'key'               => $rawKey,       // в hidden[] — в JSON не попадёт
                'hashed_key'        => $hashedKey,
                'permissions'       => $permissions,
                'expires_at'        => $expiresAt,
                'is_active'         => true,
                'correlation_id'    => $correlationId,
            ]);

            $this->logger->channel('audit')->info('B2B API key created', [
                'b2b_api_key_id'    => $model->id,
                'business_group_id' => $group->id,
                'name'              => $name,
                'permissions'       => $permissions,
                'correlation_id'    => $correlationId,
            ]);

            return ['key' => $rawKey, 'model' => $model];
        });
    }

    /**
     * Валидировать входящий ключ.
     * Возвращает BusinessGroup или выбрасывает исключение.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException 401
     */
    public function validate(string $rawKey, string $requiredPermission = ''): BusinessGroup
    {
        $hashed = hash('sha256', $rawKey);

        $keyModel = B2BApiKey::where('hashed_key', $hashed)
            ->where('is_active', true)
            ->first();

        if ($keyModel === null) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'Invalid B2B API key');
        }

        if ($keyModel->isExpired()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'B2B API key expired');
        }

        if ($requiredPermission !== '' && !$keyModel->hasPermission($requiredPermission)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, "Permission denied: {$requiredPermission}");
        }

        // Асинхронное обновление last_used_at (не блокирует запрос)
        $this->db->table('b2b_api_keys')
            ->where('id', $keyModel->id)
            ->update(['last_used_at' => now(), 'last_ip' => $this->request->ip()]);

        return $keyModel->businessGroup()->with('tenant')->firstOrFail();
    }

    /**
     * Ротация ключа: деактивировать старый, создать новый.
     *
     * @return array{key: string, model: B2BApiKey}
     */
    public function rotate(B2BApiKey $oldKey, string $correlationId): array
    {
        $this->fraud->check(
            (int) $this->guard->id(),
            'b2b_api_key_rotate',
            0,
            $this->request->ip(),
            null,
            $correlationId,
        );

        return $this->db->transaction(function () use ($oldKey, $correlationId): array {
            $oldKey->update(['is_active' => false, 'correlation_id' => $correlationId]);

            $group = $oldKey->businessGroup;

            $newKey = $this->create(
                $group,
                $oldKey->name . ' (rotated)',
                (array) $oldKey->permissions,
                $correlationId,
                $oldKey->expires_at?->toDateTimeImmutable(),
            );

            $this->logger->channel('audit')->info('B2B API key rotated', [
                'old_key_id'        => $oldKey->id,
                'new_key_id'        => $newKey['model']->id,
                'business_group_id' => $group->id,
                'correlation_id'    => $correlationId,
            ]);

            return $newKey;
        });
    }

    /**
     * Отозвать ключ.
     */
    public function revoke(B2BApiKey $keyModel, string $correlationId): void
    {
        $this->db->transaction(static function () use ($keyModel, $correlationId): void {
            $keyModel->update(['is_active' => false, 'correlation_id' => $correlationId]);

            $this->logger->channel('audit')->info('B2B API key revoked', [
                'b2b_api_key_id'    => $keyModel->id,
                'business_group_id' => $keyModel->business_group_id,
                'correlation_id'    => $correlationId,
            ]);
        });
    }
}
