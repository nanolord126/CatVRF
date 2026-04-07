<?php declare(strict_types=1);

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
 *
 * @package App\Events
 */
final class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

        private Order $order;
        private string $oldStatus;
        private string $newStatus;
        private string $correlationId;
        private int $tenantId;

        /**
         * @param Order $order
         * @param string $oldStatus
         * @param string $newStatus
         * @param string $correlationId
         */
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
            return 'order.status.changed';
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
                'old_status' => $this->oldStatus,
                'new_status' => $this->newStatus,
                'correlation_id' => $this->correlationId,
                'updated_at' => $this->order->updated_at?->toIso8601String(),
            ];
        }
}
