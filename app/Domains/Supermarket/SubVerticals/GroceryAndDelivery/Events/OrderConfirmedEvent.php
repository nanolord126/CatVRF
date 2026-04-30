<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Events;

use Psr\Log\LoggerInterface;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

final class OrderConfirmedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        public GroceryOrder $order,
        public string $correlationId) {
        $this->logger->$this->logger->info('OrderConfirmedEvent dispatched', [
            'order_id' => $order->id,
            'status' => $order->status,
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
        return 'order.confirmed';
    }
}
