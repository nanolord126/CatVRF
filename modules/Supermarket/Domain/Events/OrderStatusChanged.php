<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderStatusChanged — Событие изменения статуса заказа
 * 
 * Отправляется при изменении статуса заказа для real-time обновлений
 */
final class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $orderId,
        public readonly string $orderNumber,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly string $deliveryType,
        public readonly int $tenantId,
        public readonly ?int $businessGroupId = null,
        public readonly ?int $customerId = null,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('supermarket.orders.' . $this->customerId),
            new PrivateChannel('supermarket.tenant.' . $this->tenantId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.status.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'delivery_type' => $this->deliveryType,
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'customer_id' => $this->customerId,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
