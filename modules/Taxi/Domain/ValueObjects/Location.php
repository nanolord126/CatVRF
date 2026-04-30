<?php

declare(strict_types=1);

namespace Modules\Taxi\Domain\ValueObjects;

final readonly class Location
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?string $address = null,
    ) {
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90');
        }

        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180');
        }
    }

    public function __toString(): string
    {
        return "{$this->latitude},{$this->longitude}";
    }

    public function equals(self $other): bool
    {
        return $this->latitude === $other->latitude && $this->longitude === $other->longitude;
    }

    public function distanceTo(self $other): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($other->latitude);
        $lonTo = deg2rad($other->longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }
}
