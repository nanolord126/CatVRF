<?php declare(strict_types=1);

namespace App\Domains\Auto\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ParkingBookingCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            public readonly ParkingBooking $booking,
            public readonly string $correlationId
        ) {
            Log::channel('audit')->info('ParkingBookingCreated event dispatched', [
                'correlation_id' => $this->correlationId,
                'booking_id' => $this->booking->id,
                'spot_number' => $this->booking->spot_number,
            ]);
        }

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('tenant.' . $this->booking->tenant_id),
                new PrivateChannel('user.' . $this->booking->client_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'parking.booking.created';
        }
}
