<?php

declare(strict_types=1);

namespace Modules\Restaurant\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Models\Order;

final class KitchenOrderReady
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $correlationId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.kitchen'),
            new PrivateChannel('order.' . $this->order->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'table_id' => $this->order->table_id,
            'actual_ready_time' => $this->order->actual_ready_time?->toIso8601String(),
            'correlation_id' => $this->correlationId,
        ];
    }
}
