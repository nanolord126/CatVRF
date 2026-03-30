<?php declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\Music\Events;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicBookingCreated extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use Dispatchable, InteractsWithSockets, SerializesModels;

        public string $correlation_id;

        /**
         * Create a new event instance.
         */
        public function __construct(
            public MusicBooking $booking,
            ?string $correlation_id = null
        ) {
            $this->correlation_id = $correlation_id ?? $booking->correlation_id ?? (string) Str::uuid();
        }
}
