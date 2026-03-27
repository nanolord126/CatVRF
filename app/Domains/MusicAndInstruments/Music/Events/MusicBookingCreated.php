<?php

declare(strict_types=1);

namespace App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Events;

use App\Domains\MusicAndInstruments\MusicAndInstruments\Music\Models\MusicBooking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * MusicBookingCreated event.
 */
final class MusicBookingCreated
{
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
