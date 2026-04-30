<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class OrderProblemReported implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OrderKitchenStatus $orderStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('kitchen.' . $this->orderStatus->kitchenStationId),
            new PrivateChannel('kitchen.manager'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.problem';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderStatus->orderId,
            'kitchen_station_id' => $this->orderStatus->kitchenStationId,
            'problem_comment' => $this->orderStatus->problemComment,
            'elapsed_minutes' => $this->orderStatus->getElapsedMinutes(),
        ];
    }
}
