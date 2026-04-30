<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class OrderPriorityChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OrderKitchenStatus $orderStatus,
        public readonly string $previousPriority,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('kitchen.' . $this->orderStatus->kitchenStationId);
    }

    public function broadcastAs(): string
    {
        return 'order.priority_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderStatus->orderId,
            'kitchen_station_id' => $this->orderStatus->kitchenStationId,
            'priority' => $this->orderStatus->priority->value,
            'previous_priority' => $this->previousPriority,
        ];
    }
}
