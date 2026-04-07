<?php

declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Events;

use Dispatchable, InteractsWithSockets, SerializesModels;
use Psr\Log\LoggerInterface;

final class OrderConfirmedEvent implements ShouldBroadcast
{
        use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public GroceryOrder $order,
            public string $correlationId) {
            $this->logger->info('OrderConfirmedEvent dispatched', [
                'order_id' => $order->id,
                'status' => $order->status,
                'correlation_id' => $correlationId,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('orders.' . $this->order->user_id),
                new PrivateChannel('stores.' . $this->order->store_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'order.confirmed';
        }
    }
