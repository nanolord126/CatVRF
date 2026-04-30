<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class OrderCreated
 */
final class OrderCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithBroadcasting;
    use SerializesModels;

    private readonly Order $order;

    private readonly string $correlationId;

    private readonly int $tenantId;

    public function __construct(Order $order, string $correlationId)
    {
        $this->order = $order;
        $this->correlationId = $correlationId;
        $this->tenantId = $order->tenant_id;
    }

    /**
     * Канал для broadcast
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("tenant.{$this->tenantId}");
    }

    /**
     * Имя события в фронтенде
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Данные для broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'uuid' => $this->order->uuid,
            'status' => $this->order->status,
            'total_price' => $this->order->total_price,
            'correlation_id' => $this->correlationId,
            'created_at' => $this->order->created_at?->toIso8601String(),
        ];
    }
}
