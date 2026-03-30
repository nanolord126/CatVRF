<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AutoServiceOrderCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
