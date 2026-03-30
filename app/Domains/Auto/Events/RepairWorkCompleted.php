<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RepairWorkCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly AutoServiceOrder $order,
            public readonly string $correlationId,
        ) {
            Log::channel('audit')->info('Repair work completed', [
                'correlation_id' => $this->correlationId,
                'order_id' => $this->order->id,
                'client_id' => $this->order->client_id,
                'total_price' => $this->order->total_price,
                'tenant_id' => $this->order->tenant_id,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel("tenant.{$this->order->tenant_id}.auto.repairs"),
                new PrivateChannel("user.{$this->order->client_id}.orders"),
            ];
        }

        public function broadcastAs(): string
        {
            return 'auto.repair.completed';
        }

        public function broadcastWith(): array
        {
            return [
                'order_id' => $this->order->id,
                'vehicle_id' => $this->order->vehicle_id,
                'total_price' => $this->order->total_price,
                'completed_at' => $this->order->completed_at?->toISOString(),
                'correlation_id' => $this->correlationId,
            ];
        }

        public function shouldBroadcast(): bool
        {
            return $this->order->status === 'completed';
        }
}
