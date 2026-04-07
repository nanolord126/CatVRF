<?php

declare(strict_types=1);

/**
 * CosmeticService — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/cosmeticservice
 */


namespace App\Domains\Beauty\Cosmetics\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
final readonly class CosmeticService
{


    public function __construct(
        private FraudControlService $fraud,
        private \Illuminate\Database\DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {}

        public function orderProduct(int $productId, int $quantity, int $userId, int $tenantId, ?string $correlationId = null): CosmeticOrder
        {
            $correlationId = $correlationId ?? Str::uuid()->toString();
            $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId);
    return $this->db->transaction(function () use ($productId, $quantity, $userId, $tenantId, $correlationId) {
                $product = CosmeticProduct::lockForUpdate()->find($productId);

                if (!$product || $product->stock < $quantity) {
                    throw new \RuntimeException('Insufficient stock');
                }

                $order = CosmeticOrder::create([
                    'tenant_id' => $tenantId,
                    'uuid' => Str::uuid(),
                    'correlation_id' => $correlationId,
                    'product_id' => $productId,
                    'user_id' => $userId,
                    'quantity' => $quantity,
                    'total_price' => $product->price * $quantity,
                    'status' => 'pending',
                ]);

                $this->logger->info('Cosmetic order created', [
                    'correlation_id' => $correlationId,
                    'product_id' => $productId,
                ]);

                return $order;
            });
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

}
