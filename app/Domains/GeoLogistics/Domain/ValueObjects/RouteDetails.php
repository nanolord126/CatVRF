<?php

declare(strict_types=1);

/**
 * Class RouteDetails
 *
 * Part of the GeoLogistics vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\GeoLogistics\Domain\ValueObjects
 */
final readonly class RouteDetails
{
    public function __construct(
        public int $distanceMeters,
        public int $durationSeconds,
        public string $polyline,
        public float $cost) {}
}
