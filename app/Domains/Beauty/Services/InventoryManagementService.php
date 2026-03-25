<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\Beauty\Models\BeautyProduct;
use Illuminate\Support\Facades\DB;

/**
 * Сервис для управления инвентарём/запасами (холды, списания, пополнение).
 * Production 2026.
 */
final class InventoryManagementService
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    /**
     * Получить текущий остаток товара.
     */
    public function getCurrentStock(string $productId, string $correlationId = ''): int
    {
        $product = BeautyProduct::query()->find($productId);

        if (!$product) {
            throw new \DomainException('Product not found: ' . $productId);
        }

        return $product->current_stock;
    }

    /**
     * Зарезервировать товар (hold).
     */
    public function reserveStock(
        string $productId,
        int $quantity,
        string $reason = 'appointment_hold',
        string $correlationId = ''
    ): bool {
        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId
            );

            return $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId) {
                $product = BeautyProduct::query()->lockForUpdate()->find($productId);

                if (!$product || $product->current_stock < $quantity) {
                    throw new \DomainException('Insufficient stock for product: ' . $productId);
                }

                // Списать из текущего в зарезервированное
                $product->decrement('current_stock', $quantity);

                $this->log->channel('audit')->info('Stock reserved', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Stock reservation failed', [
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
        string $productId,
        int $quantity,
        string $reason = 'appointment_cancel',
        string $correlationId = ''
    ): bool {
        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId
            );

            return $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId) {
                $product = BeautyProduct::query()->lockForUpdate()->find($productId);

                if (!$product) {
                    throw new \DomainException('Product not found: ' . $productId);
                }

                $product->increment('current_stock', $quantity);

                $this->log->channel('audit')->info('Stock released', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Stock release failed', [
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
        string $productId,
        int $quantity,
        string $reason = 'purchase',
        string $correlationId = ''
    ): bool {
        try {
            $this->fraudControlService->check(
                auth()->id() ?? 0,
                __CLASS__ . '::' . __FUNCTION__,
                0,
                request()->ip(),
                null,
                $correlationId
            );

            return $this->db->transaction(function () use ($productId, $quantity, $reason, $correlationId) {
                $product = BeautyProduct::query()->lockForUpdate()->find($productId);

                if (!$product) {
                    throw new \DomainException('Product not found: ' . $productId);
                }

                $product->increment('current_stock', $quantity);

                $this->log->channel('audit')->info('Stock added', [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('Stock addition failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }
}
