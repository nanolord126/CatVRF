<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\GroceryAndDelivery\Events;

use Psr\Log\LoggerInterface;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

final class DeliveryAssignedEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(private readonly LoggerInterface $logger,
        public GroceryOrder $order,
        public int $partnerId,
        public string $correlationId) {
        $this->logger->$this->logger->info('DeliveryAssignedEvent dispatched', [
            'order_id' => $order->id,
            'partner_id' => $partnerId,
            'correlation_id' => $correlationId,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->order->user_id),
            new PrivateChannel('deliveries.'.$this->partnerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'delivery.assigned';
    }
}
