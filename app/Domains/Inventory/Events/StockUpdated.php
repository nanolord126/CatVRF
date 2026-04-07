<?php

declare(strict_types=1);

namespace App\Domains\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Остатки на складе изменились.
 *
 * Универсальное событие для любого изменения quantity/reserved.
 * Используется для реал-тайм обновления UI через Echo.
 */
final class StockUpdated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int    $productId,
        public readonly int    $warehouseId,
        public readonly int    $newQuantity,
        public readonly int    $newReserved,
        public readonly int    $available,
        public readonly int    $tenantId,
        public readonly string $correlationId,
    ) {}

    /** @return array<string, mixed> */
    public function broadcastPayload(): array
    {
        return [
            'product_id'     => $this->productId,
            'warehouse_id'   => $this->warehouseId,
            'quantity'        => $this->newQuantity,
            'reserved'       => $this->newReserved,
            'available'      => $this->available,
            'tenant_id'      => $this->tenantId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
