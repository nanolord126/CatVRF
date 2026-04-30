<?php

declare(strict_types=1);

namespace App\Listeners\Inventory;

use App\Events\Inventory\StockMoved;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidate Inventory Cache Listener
 *
 * Invalidates cache tags when stock is moved.
 * Ensures data consistency across the system.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final readonly class InvalidateInventoryCache
{
    public function handle(StockMoved $event): void
    {
        // Invalidate cache for the specific item
        Cache::tags(['inventory', "item:{$event->itemId}"])->flush();

        // Invalidate cache for the warehouse
        Cache::tags(['warehouse', "warehouse:{$event->fromWarehouseId}"])->flush();

        if ($event->toWarehouseId) {
            Cache::tags(['warehouse', "warehouse:{$event->toWarehouseId}"])->flush();
        }

        // Invalidate tenant-wide inventory cache
        Cache::tags(['inventory', "tenant:{$event->tenantId}"])->flush();
    }
}
