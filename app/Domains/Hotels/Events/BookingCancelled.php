<?php declare(strict_types=1);

namespace App\Domains\Hotels\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class BookingCancelled extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, SerializesModels;

        public function __construct(
            public readonly Booking $booking,
            public readonly string $correlationId
        ) {}
}
