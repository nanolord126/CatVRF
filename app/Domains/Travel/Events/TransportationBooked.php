<?php declare(strict_types=1);

namespace App\Domains\Travel\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TransportationBooked extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public TravelTransportation $transportation,
            public string $correlationId,
        ) {}

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('travel.transportation'),
            ];
        }

        public function broadcastAs(): string
        {
            return 'transportation.booked';
        }
}
