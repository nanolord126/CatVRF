<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\AutoServiceOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания заказа-наряда СТО.
 * Production 2026.
 */
final class AutoServiceOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly AutoServiceOrder $order,
        public readonly string $correlationId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\Channel('auto.service-orders.' . $this->order->tenant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AutoServiceOrderCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'service_type' => $this->order->service_type,
            'appointment_datetime' => $this->order->appointment_datetime?->toIso8601String(),
            'status' => $this->order->status,
            'correlation_id' => $this->correlationId,
        ];
    }
}
