<?php declare(strict_types=1);

/**
 * MusicBookingCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/musicbookingcreated
 */


namespace App\Domains\MusicAndInstruments\Music\Events;

final class MusicBookingCreated
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        private string $correlation_id;

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
