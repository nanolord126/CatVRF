<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Инвентаризация обновлена (статус изменился).
 */
final class InventoryCheckUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int    $inventoryCheckId,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly int    $tenantId,
        public readonly string $correlationId,
    ) {}

    /** @return array<string, mixed> */
    public function broadcastPayload(): array
    {
        return [
            'inventory_check_id' => $this->inventoryCheckId,
            'old_status'         => $this->oldStatus,
            'new_status'         => $this->newStatus,
            'tenant_id'          => $this->tenantId,
            'correlation_id'     => $this->correlationId,
        ];
    }
}
