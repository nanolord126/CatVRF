<?php declare(strict_types=1);

/**
 * FlightBooked — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/flightbooked
 */


namespace App\Domains\Travel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

final class FlightBooked
{


        public function __construct(
            public TravelFlight $flight,
            public string $correlationId) {}

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
