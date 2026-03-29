declare(strict_types=1);

namespace App\Domains\GroceryAndDelivery\Events;

use App\Domains\GroceryAndDelivery\Models\GroceryOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class OrderCreatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GroceryOrder $order,
        public string $correlationId,
    ) {
        Log::channel('audit')->info('OrderCreatedEvent dispatched', [
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
            new PrivateChannel('orders.' . $this->order->user_id),
            new PrivateChannel('stores.' . $this->order->store_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }
}

final class OrderConfirmedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GroceryOrder $order,
        public string $correlationId,
    ) {
        Log::channel('audit')->info('OrderConfirmedEvent dispatched', [
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

final class OrderDeliveredEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GroceryOrder $order,
        public string $correlationId,
    ) {
        Log::channel('audit')->info('OrderDeliveredEvent dispatched', [
            'order_id' => $order->id,
            'total_price' => $order->total_price,
            'commission_amount' => $order->commission_amount,
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
        return 'order.delivered';
    }
}

final class OrderCancelledEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GroceryOrder $order,
        public string $reason,
        public string $correlationId,
    ) {
        Log::channel('audit')->info('OrderCancelledEvent dispatched', [
            'order_id' => $order->id,
            'reason' => $reason,
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
        return 'order.cancelled';
    }
}

final class DeliveryAssignedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GroceryOrder $order,
        public int $partnerId,
        public string $correlationId,
    ) {
        Log::channel('audit')->info('DeliveryAssignedEvent dispatched', [
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
