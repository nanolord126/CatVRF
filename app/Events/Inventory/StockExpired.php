<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stock Expired Event
 *
 * Fired when inventory batch expires.
 * Triggers compliance notifications and quarantine actions.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class StockExpired
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $batchId,
        public readonly int $productId,
        public readonly string $sku,
        public readonly string $batchNumber,
        public readonly string $expiryDate,
        public readonly int $quantity,
        public readonly int $warehouseId,
        public readonly int $tenantId,
        public readonly ?string $correlationId = null
    ) {}
}
