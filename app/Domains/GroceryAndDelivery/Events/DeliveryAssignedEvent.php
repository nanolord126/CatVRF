<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Events;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

use Psr\Log\LoggerInterface;

final class DeliveryAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
        
        public function __construct(
            public GroceryOrder $order,
            public int $partnerId,
            public string $correlationId) {
            $this->logger->info('DeliveryAssignedEvent dispatched', [
                'order_id' => $order->id,
                'partner_id' => $partnerId,
                'correlation_id' => $correlationId,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('orders.' . $this->order->user_id),
                new PrivateChannel('deliveries.' . $this->partnerId),
            ];
        }

        public function broadcastAs(): string
        {
            return 'delivery.assigned';
        }
}
