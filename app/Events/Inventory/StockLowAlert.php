<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stock Low Alert Event
 *
 * Fired when inventory item falls below minimum stock threshold.
 * Triggers reorder notifications and alerts.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class StockLowAlert
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $itemId,
        public readonly string $sku,
        public readonly string $itemName,
        public readonly int $currentStock,
        public readonly int $minStockThreshold,
        public readonly int $tenantId,
        public readonly ?string $correlationId = null
    ) {}
}
