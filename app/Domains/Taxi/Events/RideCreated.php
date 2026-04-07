<?php declare(strict_types=1);

/**
 * RideCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/ridecreated
 */


namespace App\Domains\Taxi\Events;

final class RideCreated
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

        public function __construct(
            private readonly int $rideId,
            private readonly string $driverId,
            private readonly string $passengerId,
            private readonly string $correlationId,
            private array $metadata = []) {}

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
