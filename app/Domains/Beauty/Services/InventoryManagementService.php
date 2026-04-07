<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\BeautyConsumable;
use App\Services\FraudControlService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * InventoryManagementService — управление остатками расходных материалов.
 *
 * Резервирование, отпуск и добавление запасов с fraud-проверкой,
 * транзакционной целостностью и audit-логированием.
 */
final readonly class InventoryManagementService
{
    public function __construct(
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
        private Guard $guard,
    ) {
    }

    /**
     * Получить текущий остаток товара.
     */
    public function getCurrentStock(int $productId, string $correlationId = ''): int
    {
        $product = BeautyConsumable::query()->find($productId);

        if ($product === null) {
            throw new \DomainException('Product not found: ' . $productId);
        }

        return (int) $product->current_stock;
    }

    /**
     * Зарезервировать товар (hold).
     */
    public function reserveStock(
        int $productId,
        int $quantity,
        string $reason = 'appointment_hold',
        string $correlationId = '',
    ): bool {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'inventory_reserve',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            return $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId): bool {
                $product = BeautyConsumable::query()->lockForUpdate()->find($productId);

                if ($product === null || $product->current_stock < $quantity) {
                    throw new \DomainException('Insufficient stock for product: ' . $productId);
                }

                $product->decrement('current_stock', $quantity);

                $this->logger->info('Stock reserved', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->logger->error('Stock reservation failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Отпустить зарезервированный товар.
     */
    public function releaseStock(
        int $productId,
        int $quantity,
        string $reason = 'appointment_cancel',
        string $correlationId = '',
    ): bool {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'inventory_release',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            return $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId): bool {
                $product = BeautyConsumable::query()->lockForUpdate()->find($productId);

                if ($product === null) {
                    throw new \DomainException('Product not found: ' . $productId);
                }

                $product->increment('current_stock', $quantity);

                $this->logger->info('Stock released', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->logger->error('Stock release failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Добавить запас товара.
     */
    public function addStock(
        int $productId,
        int $quantity,
        string $reason = 'purchase',
        string $correlationId = '',
    ): bool {
        $this->fraud->check(
            userId: (int) ($this->guard->id() ?? 0),
            operationType: 'inventory_add',
            amount: 0,
            correlationId: $correlationId,
        );

        try {
            return $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId): bool {
                $product = BeautyConsumable::query()->lockForUpdate()->find($productId);

                if ($product === null) {
                    throw new \DomainException('Product not found: ' . $productId);
                }

                $product->increment('current_stock', $quantity);

                $this->logger->info('Stock added', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->logger->error('Stock addition failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
