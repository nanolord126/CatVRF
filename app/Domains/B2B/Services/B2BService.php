<?php declare(strict_types=1);

namespace App\Domains\B2B\Services;

use App\Domains\B2B\DTOs\CreateApiKeyDto;
use App\Domains\B2B\DTOs\CreateOrderDto;
use App\Domains\B2B\Models\BusinessGroup;
use App\Domains\B2B\Models\B2BApiKey;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Carbon\CarbonInterface;

final readonly class B2BService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly Request $request,
        private readonly Guard $guard,
        private readonly CarbonInterface $carbon,
    ) {}

    /**
     * Create API key for business group
     */
    public function createApiKey(CreateApiKeyDto $dto, string $correlationId): array
    {
        $correlationId ??= Str::uuid()->toString();

        $this->fraud->check([
            'operation_type' => 'b2b_api_key_create',
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $correlationId) {
            $rawKey = 'b2b_' . bin2hex(random_bytes(32));
            $hashedKey = hash('sha256', $rawKey);

            $apiKey = B2BApiKey::create([
                'business_group_id' => $dto->businessGroupId,
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
                'name' => $dto->name,
                'hashed_key' => $hashedKey,
                'permissions' => $dto->permissions,
                'expires_at' => $dto->expiresAt,
                'is_active' => true,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->record(
                action: 'b2b_api_key_created',
                subjectType: B2BApiKey::class,
                subjectId: $apiKey->id,
                newValues: ['name' => $dto->name],
                correlationId: $correlationId,
            );

            $this->logger->info('B2B API key created', [
                'b2b_api_key_id' => $apiKey->id,
                'business_group_id' => $dto->businessGroupId,
                'correlation_id' => $correlationId,
            ]);

            return ['key' => $rawKey, 'model' => $apiKey];
        });
    }

    /**
     * Validate API key
     */
    public function validateApiKey(string $rawKey, string $requiredPermission = ''): BusinessGroup
    {
        $hashed = hash('sha256', $rawKey);

        $apiKey = B2BApiKey::where('hashed_key', $hashed)
            ->active()
            ->first();

        if (!$apiKey) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'Invalid B2B API key');
        }

        if ($apiKey->isExpired()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'B2B API key expired');
        }

        if ($requiredPermission && !$apiKey->hasPermission($requiredPermission)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, "Permission denied: {$requiredPermission}");
        }

        $apiKey->update(['last_used_at' => $this->carbon->now(), 'last_ip' => $this->request->ip()]);

        return $apiKey->businessGroup;
    }

    /**
     * Revoke API key
     */
    public function revokeApiKey(int $apiKeyId, string $correlationId): void
    {
        $correlationId ??= Str::uuid()->toString();

        $this->db->transaction(function () use ($apiKeyId, $correlationId) {
            B2BApiKey::findOrFail($apiKeyId)->update([
                'is_active' => false,
            ]);

            $this->audit->record(
                action: 'b2b_api_key_revoked',
                subjectType: B2BApiKey::class,
                subjectId: $apiKeyId,
                correlationId: $correlationId,
            );

            $this->logger->info('B2B API key revoked', [
                'b2b_api_key_id' => $apiKeyId,
                'correlation_id' => $correlationId,
            ]);
        });
    }

    /**
     * Create B2B order
     */
    public function createOrder(CreateOrderDto $dto, string $correlationId): array
    {
        $correlationId ??= Str::uuid()->toString();

        $total = $this->calculateTotal($dto->items);

        $this->fraud->check([
            'operation_type' => 'b2b_order_create',
            'amount' => $total,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($dto, $total, $correlationId) {
            $group = BusinessGroup::findOrFail($dto->businessGroupId);

            if ($dto->useCredit) {
                if (!$group->hasCredit($total)) {
                    throw new \DomainException('Insufficient credit limit');
                }
                $group->decrement('credit_limit', $total);
            }

            $orderId = $this->db->table('orders')->insertGetId([
                'uuid' => Str::uuid()->toString(),
                'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : 1,
                'business_group_id' => $dto->businessGroupId,
                'user_id' => $this->guard->id(),
                'status' => 'pending',
                'is_b2b' => true,
                'total_kopecks' => $total,
                'payment_type' => $dto->useCredit ? 'credit' : 'prepaid',
                'delivery_address' => $dto->deliveryAddress,
                'correlation_id' => $correlationId,
                'created_at' => $this->carbon->now(),
                'updated_at' => $this->carbon->now(),
            ]);

            $this->audit->record(
                action: 'b2b_order_created',
                subjectType: 'Order',
                subjectId: $orderId,
                newValues: [
                    'business_group_id' => $dto->businessGroupId,
                    'total' => $total,
                ],
                correlationId: $correlationId,
            );

            $this->logger->info('B2B order created', [
                'order_id' => $orderId,
                'total' => $total,
                'correlation_id' => $correlationId,
            ]);

            return [
                'order_id' => $orderId,
                'total' => $total,
                'use_credit' => $dto->useCredit,
            ];
        });
    }

    private function calculateTotal(array $items): int
    {
        $total = 0;
        foreach ($items as $item) {
            $price = $this->getWholesalePrice($item['product_id']);
            $total += $price * $item['quantity'];
        }
        return $total;
    }

    private function getWholesalePrice(int $productId): int
    {
        $price = $this->db->table('products')
            ->where('id', $productId)
            ->value('wholesale_price_kopecks');

        if ($price === null) {
            $retail = (int) $this->db->table('products')
                ->where('id', $productId)
                ->value('price_kopecks');
            return (int) round($retail * 0.8);
        }

        return (int) $price;
    }
}
