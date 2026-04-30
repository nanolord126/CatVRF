<?php

declare(strict_types=1);

namespace App\Events\Inventory;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stock Moved Event
 *
 * Fired when stock is moved between locations or allocated to orders.
 * Triggers cache invalidation and notifications.
 *
 * @author CatVRF Team
 * @version 2026.04.28
 */
final class StockMoved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $itemId,
        public readonly int $quantity,
        public readonly string $movementType,
        public readonly int $fromWarehouseId,
        public readonly ?int $toWarehouseId,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly ?string $orderId = null,
        public readonly ?string $correlationId = null
    ) {}
}
