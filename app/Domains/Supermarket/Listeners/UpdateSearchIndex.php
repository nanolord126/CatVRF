<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\Listeners;

use App\Domains\Supermarket\Events\InventoryUpdated;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * UpdateSearchIndex - обновление поискового индекса при изменении инвентаря.
 *
 * Обновляет доступность товаров в поисковом индексе (Meilisearch/Elasticsearch).
 * Запускается асинхронно через Job для избежания блокировки.
 */
final readonly class UpdateSearchIndex
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(InventoryUpdated $event): void
    {
        try {
            $product = \App\Models\Product::find($event->productId);
            if ($product) {
                $product->searchable();
            }

            $this->logger->info('Search index update triggered', [
                'product_id' => $event->productId,
                'delta' => $event->delta,
                'reason' => $event->reason,
                'order_id' => $event->orderId,
                'correlation_id' => $event->correlationId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update search index', [
                'product_id' => $event->productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
