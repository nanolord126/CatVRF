<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Venue
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $address,
        public string $city,
        public string $phone,
        public ?string $email,
        public ?array $workingHours,
        public ?float $latitude,
        public ?float $longitude,
        public bool $isActive,
        public bool $supportsDelivery,
        public bool $supportsPickup,
        public int $deliveryRadiusKm,
        public int $preparationTimeMinutes,
        public ?array $settings,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $slug,
        string $address,
        string $city,
        string $phone,
        ?string $description = null,
        ?string $email = null,
        ?array $workingHours = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?array $settings = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            slug: $slug,
            description: $description,
            address: $address,
            city: $city,
            phone: $phone,
            email: $email,
            workingHours: $workingHours,
            latitude: $latitude,
            longitude: $longitude,
            isActive: true,
            supportsDelivery: true,
            supportsPickup: true,
            deliveryRadiusKm: 15,
            preparationTimeMinutes: 30,
            settings: $settings,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function isWithinDeliveryRange(float $latitude, float $longitude): bool
    {
        if (!$this->latitude || !$this->longitude) {
            return false;
        }

        $distance = $this->calculateDistance($latitude, $longitude);
        return $distance <= $this->deliveryRadiusKm;
    }

    private function calculateDistance(float $latitude, float $longitude): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2 +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) ** 2;

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }

    public function isOpenAt(CarbonImmutable $dateTime): bool
    {
        if (!$this->workingHours) {
            return true;
        }

        $dayOfWeek = strtolower($dateTime->englishDayOfWeek);
        $time = $dateTime->format('H:i');

        if (!isset($this->workingHours[$dayOfWeek])) {
            return false;
        }

        $hours = $this->workingHours[$dayOfWeek];
        if (!$hours['is_open'] ?? false) {
            return false;
        }

        return $time >= ($hours['open'] ?? '00:00') && $time <= ($hours['close'] ?? '23:59');
    }
}
