<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Restaurant\Domain\Entities\OrderKitchenStatus;

final class OrderSentToKitchen implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OrderKitchenStatus $orderStatus,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('kitchen.' . $this->orderStatus->kitchenStationId);
    }

    public function broadcastAs(): string
    {
        return 'order.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderStatus->orderId,
            'kitchen_station_id' => $this->orderStatus->kitchenStationId,
            'status' => $this->orderStatus->status->value,
            'priority' => $this->orderStatus->priority->value,
            'estimated_minutes' => $this->orderStatus->estimatedPreparationTime->minutes,
            'is_vip' => $this->orderStatus->isVip,
            'is_from_marketplace' => $this->orderStatus->isFromMarketplace,
        ];
    }
}
