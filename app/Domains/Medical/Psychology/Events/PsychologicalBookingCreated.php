<?php

declare(strict_types=1);

namespace App\Domains\Medical\Psychology\Events;

use App\Domains\Medical\Psychology\Models\PsychologicalBooking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие создания записи на психологическую сессию.
 */
final class PsychologicalBookingCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PsychologicalBooking $booking,
        public string $correlationId
    ) {}
}
