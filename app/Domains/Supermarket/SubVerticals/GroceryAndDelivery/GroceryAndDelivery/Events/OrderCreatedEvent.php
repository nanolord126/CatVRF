<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Events;

use Psr\Log\LoggerInterface;

final class OrderCreatedEvent
{
    public function __construct(
        public GroceryOrder $order,
        public string $correlationId,
        public readonly LoggerInterface $logger
    ) {
        $this->logger->$this->logger->info('OrderCreatedEvent dispatched', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'store_id' => $order->store_id,
            'total_price' => $order->total_price,
            'correlation_id' => $correlationId,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->order->user_id),
            new PrivateChannel('stores.'.$this->order->store_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}
