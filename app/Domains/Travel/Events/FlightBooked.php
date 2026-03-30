<?php declare(strict_types=1);

namespace App\Domains\Travel\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class FlightBooked extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public TravelFlight $flight,
            public string $correlationId,
        ) {}

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('travel.flights'),
            ];
        }

        public function broadcastAs(): string
        {
            return 'flight.booked';
        }
}
