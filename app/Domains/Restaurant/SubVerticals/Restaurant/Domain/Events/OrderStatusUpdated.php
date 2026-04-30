<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OrderKitchenStatus $orderStatus,
        public readonly string $previousStatus,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('kitchen.' . $this->orderStatus->kitchenStationId);
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderStatus->orderId,
            'kitchen_station_id' => $this->orderStatus->kitchenStationId,
            'status' => $this->orderStatus->status->value,
            'previous_status' => $this->previousStatus,
            'priority' => $this->orderStatus->priority->value,
            'elapsed_minutes' => $this->orderStatus->getElapsedMinutes(),
            'time_remaining' => $this->orderStatus->getTimeRemaining(),
            'is_overdue' => $this->orderStatus->isOverdue(),
            'progress' => $this->orderStatus->getProgress(),
        ];
    }
}
