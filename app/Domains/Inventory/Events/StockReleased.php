<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Резерв снят со склада.
 *
 * Диспатчится после InventoryService::releaseReservation().
 * Уведомляет UI о возвращении товара в доступные.
 */
final class StockReleased
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int    $productId,
        public readonly int    $warehouseId,
        public readonly int    $quantity,
        public readonly int    $tenantId,
        public readonly string $correlationId,
    ) {}

    /** @return array<string, mixed> */
    public function broadcastPayload(): array
    {
        return [
            'product_id'     => $this->productId,
            'warehouse_id'   => $this->warehouseId,
            'quantity'        => $this->quantity,
            'tenant_id'      => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
