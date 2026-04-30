<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

/**
 * Event: Order status changed.
 * Broadcast: private-tenant.{tenantId}
 */
final class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithBroadcasting;
    use SerializesModels;

    private readonly Order $order;

    private readonly string $oldStatus;

    private readonly string $newStatus;

    private readonly string $correlationId;

    private readonly int $tenantId;

    public function __construct(
        Order $order,
        string $oldStatus,
        string $newStatus,
        string $correlationId
    ) {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
        return 'order.status.changed';
    }

    /**
     * Данные для broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'uuid' => $this->order->uuid,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'correlation_id' => $this->correlationId,
            'updated_at' => $this->order->updated_at?->toIso8601String(),
        ];
    }
}
