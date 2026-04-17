<?php declare(strict_types=1);

namespace App\Services;

use App\Models\UserAddress;
use App\Services\Geo\GeoPrivacyService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;


use Illuminate\Support\Str;
use App\Services\FraudControlService;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class UserAddressService
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly ConfigRepository $config,
        private readonly FraudControlService $fraud,
        private readonly GeoPrivacyService $geoPrivacy,
    ) {}

    /**
     * Добавить или вернуть существующий адрес с конфигурируемым лимитом
     */
    public function addOrGetAddress(
        int $userId,
        string $address,
        string $type = 'other',
        ?string $vertical = null,
        ?int $tenantId = null,
    ): UserAddress {
        $correlationId = Str::uuid()->toString();

        $this->fraud->check(
            userId: $userId,
            operationType: 'address_add',
            amount: 0,
            ipAddress: request()->ip(),
            deviceFingerprint: null,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($userId, $address, $type, $vertical, $tenantId, $correlationId): UserAddress {
            $existing = UserAddress::query()->where([
                'user_id' => $userId,
                'address' => $address,
            ])->first();

            if ($existing instanceof UserAddress) {
                $existing->increment('usage_count');

                $this->logger->channel('audit')->info('User address reused', [
                    'user_id' => $userId,
                    'address_id' => $existing->id,
                    'correlation_id' => $correlationId,
                ]);

                return $existing->refresh();
            }

            $maxAddresses = $this->getMaxAddresses($vertical, $tenantId);
            $count = UserAddress::query()->where('user_id', $userId)->count();
            
            if ($count >= $maxAddresses) {
                UserAddress::query()->where('user_id', $userId)
                    ->orderBy('usage_count')
                    ->orderBy('id')
                    ->limit(1)
                    ->delete();
            }

            $created = UserAddress::query()->create([
                'user_id' => $userId,
                'address' => $address,
                'type' => $type,
                'vertical' => $vertical,
                'tenant_id' => $tenantId,
                'usage_count' => 1,
            ]);

            $this->logger->channel('audit')->info('User address created', [
                'user_id' => $userId,
                'address_id' => $created->id,
                'vertical' => $vertical,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $created;
        });
    }

    /**
     * Получить историю адресов пользователя
     */
    public function getAddressHistory(int $userId, ?string $vertical = null, int $limit = null): Collection
    {
        $query = UserAddress::where('user_id', $userId);

        if ($vertical) {
            $query->where('vertical', $vertical);
        }

        $limit = $limit ?? $this->config->get('geo.addresses.max_per_user', 5);

        return $query->orderBy('usage_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить максимальное количество адресов для пользователя
     */
    private function getMaxAddresses(?string $vertical, ?int $tenantId): int
    {
        // Check per-vertical config
        if ($vertical) {
            $verticalLimit = $this->config->get("geo.addresses.per_vertical.{$vertical}");
            if ($verticalLimit !== null) {
                return $verticalLimit;
            }
        }

        // Check per-tenant config
        if ($tenantId) {
            $tenantConfig = $this->config->get('geo.addresses.per_tenant');
            if (isset($tenantConfig[$tenantId])) {
                return $tenantConfig[$tenantId];
            }
        }

        // Default limit
        return $this->config->get('geo.addresses.max_per_user', 5);
    }

    /**
     * Анонимировать адрес для логов/аналитики
     */
    public function anonymizeAddress(string $address): string
    {
        // Remove sensitive details but keep general location
        $parts = explode(',', $address);
        
        if (count($parts) > 2) {
            // Keep first and last parts, mask middle
            return $parts[0] . ', ***, ' . end($parts);
        }

        return str_repeat('*', min(strlen($address), 10));
    }

    /**
     * Удалить адрес
     */
    public function deleteAddress(int $userId, int $addressId): bool
    {
        $deleted = UserAddress::where('id', $addressId)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted) {
            $this->logger->channel('audit')->info('User address deleted', [
                'user_id' => $userId,
                'address_id' => $addressId,
                'correlation_id' => Str::uuid()->toString(),
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Получить адрес по ID с проверкой прав доступа
     */
    public function getAddress(int $userId, int $addressId): ?UserAddress
    {
        return UserAddress::where('id', $addressId)
            ->where('user_id', $userId)
            ->first();
    }
}
