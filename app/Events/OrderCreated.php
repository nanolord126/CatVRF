<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания заказа (Real-Time)
 * Триггер: POST /api/orders
 * Broadcast: private-tenant.{tenantId}
 * 
 * @package App\Events
 */
final class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public readonly Order $order;
    public readonly string $correlationId;
    public readonly int $tenantId;

    /**
     * @param Order $order
     * @param string $correlationId
     */
    public function __construct(Order $order, string $correlationId)
    {
        $this->order = $order;
        $this->correlationId = $correlationId;
        $this->tenantId = $order->tenant_id;
    }

    /**
     * Канал для broadcast
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("tenant.{$this->tenantId}");
    }

    /**
     * Имя события в фронтенде
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Данные для broadcast
     * @return array
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
