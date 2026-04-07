<?php

declare(strict_types=1);

namespace App\Domains\Auto\Taxi\Domain\ValueObjects;

use App\Shared\Domain\ValueObject\ValueObject;
use InvalidArgumentException;

/**
 * Class Coordinate
 *
 * Part of the Auto vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Component of the CatVRF platform.
 * Follows strict coding standards:
 * - final class (no inheritance unless required)
 * - private readonly properties
 * - Constructor injection only
 * - correlation_id in all operations
 *
 * @package App\Domains\Auto\Taxi\Domain\ValueObjects
 */
final class Coordinate extends ValueObject
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90.');
        }
        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180.');
        }
    }

    /**
     * Handle toArray operation.
     *
     * @throws \DomainException
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Handle equals operation.
     *
     * @throws \DomainException
     */
    public function equals(ValueObject $other): bool
    {
        return $other instanceof self &&
            $this->latitude === $other->latitude &&
            $this->longitude === $other->longitude;
    }
}
