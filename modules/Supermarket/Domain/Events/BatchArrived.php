<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * BatchArrived — Событие прибытия партии на склад
 * 
 * Отправляется при поступлении новой партии товаров на склад
 */
final class BatchArrived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $batchId,
        public readonly int $warehouseId,
        public readonly string $warehouseName,
        public readonly int $productCount,
        public readonly int $totalQuantity,
        public readonly float $estimatedValue,
        public readonly int $tenantId,
        public readonly ?int $businessGroupId = null,
        public readonly array $products = [],
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('supermarket.warehouse.' . $this->warehouseId),
            new PrivateChannel('supermarket.tenant.' . $this->tenantId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'batch.arrived';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'batch_id' => $this->batchId,
            'warehouse_id' => $this->warehouseId,
            'warehouse_name' => $this->warehouseName,
            'product_count' => $this->productCount,
            'total_quantity' => $this->totalQuantity,
            'estimated_value' => $this->estimatedValue,
            'products' => $this->products,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
