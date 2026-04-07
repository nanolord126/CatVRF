<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Инвентаризация создана.
 */
final class InventoryCheckCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int    $inventoryCheckId,
        public readonly int    $warehouseId,
        public readonly int    $tenantId,
        public readonly string $correlationId,
    ) {}

    /** @return array<string, mixed> */
    public function broadcastPayload(): array
    {
        return [
            'inventory_check_id' => $this->inventoryCheckId,
            'warehouse_id'       => $this->warehouseId,
            'tenant_id'          => $this->tenantId,
            'correlation_id'     => $this->correlationId,
        ];
    }
}
