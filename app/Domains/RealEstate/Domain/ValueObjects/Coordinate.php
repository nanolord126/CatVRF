<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Coordinate
{
    public function __construct(
        private float $latitude,
        private float $longitude) {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidArgumentException("Latitude must be between -90 and 90, got {$latitude}.");
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidArgumentException("Longitude must be between -180 and 180, got {$longitude}.");
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) $data['lat'],
            longitude: (float) $data['lon'],
        );
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Calculates distance in metres using the Haversine formula.
     */
    public function distanceTo(self $other): float
    {
        $earthRadius = 6_371_000.0;

        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($other->latitude);
        $deltaLat = deg2rad($other->latitude - $this->latitude);
        $deltaLon = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;

        return $earthRadius * 2.0 * atan2(sqrt($a), sqrt(1.0 - $a));
    }

    public function toArray(): array
    {
        return [
            'lat' => $this->latitude,
            'lon' => $this->longitude,
        ];
    }

    public function equals(self $other): bool
    {
        return abs($this->latitude - $other->latitude) < 1e-7
            && abs($this->longitude - $other->longitude) < 1e-7;
    }
}
