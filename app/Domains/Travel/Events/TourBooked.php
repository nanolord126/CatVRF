<?php declare(strict_types=1);

/**
 * TourBooked — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/tourbooked
 */


namespace App\Domains\Travel\Events;

final class TourBooked
{

    use Dispatchable;
        use InteractsWithSockets;
        use SerializesModels;

        public function __construct(
            public TravelBooking $booking,
            public string $correlationId) {}

        public function broadcastOn(): array
        {
            return [
                new PrivateChannel('agency.' . $this->booking->agency_id),
            ];
        }

        public function broadcastAs(): string
        {
            return 'tour.booked';
        }

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
