<?php declare(strict_types=1);

namespace App\Domains\Archived\Taxi\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RideCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;


        public function __construct(


            public readonly int $rideId,


            public readonly string $driverId,


            public readonly string $passengerId,


            public readonly string $correlationId,


            public readonly array $metadata = [],


        ) {}
}
