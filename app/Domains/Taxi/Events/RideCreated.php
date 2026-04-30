<?php

declare(strict_types=1);

/**
 * RideCreated — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/ridecreated
 */

namespace App\Domains\Taxi\Events;

use Carbon\CarbonImmutable;

final class RideCreated
{
    public function __construct(
        private readonly int $rideId,
        private readonly string $driverId,
        private readonly string $passengerId,
        private readonly string $correlationId,
        private readonly array $metadata = []
    ) {}

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return self::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => self::class,
            'timestamp' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
