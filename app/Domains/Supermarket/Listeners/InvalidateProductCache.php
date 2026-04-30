<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Listeners;

use App\Domains\Supermarket\Events\InventoryUpdated;
use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * InvalidateProductCache - инвалидация кэша продуктов при изменении инвентаря.
 *
 * Удаляет кэш популярных продуктов и других product-related кэшей.
 */
final readonly class InvalidateProductCache
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(InventoryUpdated $event): void
    {
        try {
            // Инвалидируем кэш популярных продуктов для всех под-вертикалей
            Cache::tags(['supermarket', 'products'])->flush();

            // Инвалидируем кэш конкретного продукта
            Cache::forget("supermarket:product:{$event->productId}");

            // Инвалидируем кэш слотов доставки (так как доступность может измениться)
            Cache::tags(['supermarket', 'delivery_slots'])->flush();

            $this->logger->info('Product cache invalidated', [
                'product_id' => $event->productId,
                'delta' => $event->delta,
                'reason' => $event->reason,
                'order_id' => $event->orderId,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to invalidate product cache', [
                'product_id' => $event->productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
