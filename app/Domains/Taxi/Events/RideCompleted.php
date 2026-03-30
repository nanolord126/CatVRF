<?php declare(strict_types=1);

namespace App\Domains\Taxi\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RideCompleted extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public function __construct(
            readonly public TaxiRide $ride,
            readonly public string $correlationId = '',
        ) {
        }
}
