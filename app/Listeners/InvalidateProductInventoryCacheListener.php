<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final readonly class InvalidateProductInventoryCacheListener
{
    public function handle(object $event): void
    {
        if (!isset($event->productId)) {
            return;
        }

        try {
            $cacheTag = "product_inventory_{$event->productId}";
            Cache::store('redis')->tags([$cacheTag])->flush();

            // Also flush popular products for the vertical
            if (isset($event->vertical)) {
                $verticalTag = "popular_products_{$event->vertical}";
                Cache::store('redis')->tags([$verticalTag])->flush();
            }

            Log::channel('audit')->info('Product inventory cache invalidated', [
                'product_id' => $event->productId,
                'vertical' => $event->vertical ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to invalidate product inventory cache', [
                'product_id' => $event->productId ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
