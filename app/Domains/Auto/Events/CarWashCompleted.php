<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarWashCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly CarWashBooking $booking,
            public readonly string $correlationId
        ) {
        }

        public function broadcastOn(): array
        {
            return [
                new \Illuminate\Broadcasting\Channel('auto.car-wash.' . $this->booking->tenant_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'CarWashCompleted';
        }

        public function broadcastWith(): array
        {
            return [
                'booking_id' => $this->booking->id,
                'wash_type' => $this->booking->wash_type,
                'completed_at' => $this->booking->completed_at?->toIso8601String(),
                'price' => $this->booking->price,
                'correlation_id' => $this->correlationId,
            ];
        }
}
