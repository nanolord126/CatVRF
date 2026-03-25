declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use App\Domains\Auto\Models\CarWashBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие завершения мойки.
 * Production 2026.
 */
final class CarWashCompleted implements ShouldBroadcast
{
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
