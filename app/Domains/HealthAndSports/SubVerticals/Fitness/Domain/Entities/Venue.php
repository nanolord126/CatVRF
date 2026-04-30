<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Venue
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public string $address,
        public ?string $city,
        public ?string $phone,
        public ?string $email,
        public float $latitude,
        public float $longitude,
        public int $capacity,
        public int $totalArea,
        public ?array $amenities,
        public ?string $openingHours,
        public bool $isActive,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $address,
        float $latitude = 0.0,
        float $longitude = 0.0,
        int $capacity = 100,
        int $totalArea = 500,
        ?string $description = null,
        ?string $city = null,
        ?string $phone = null,
        ?string $email = null,
        ?array $amenities = null,
        ?string $openingHours = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            address: $address,
            city: $city,
            phone: $phone,
            email: $email,
            latitude: $latitude,
            longitude: $longitude,
            capacity: $capacity,
            totalArea: $totalArea,
            amenities: $amenities,
            openingHours: $openingHours,
            isActive: true,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function activate(): self
    {
        return new self(
            ...get_object_vars($this),
            isActive: true,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...get_object_vars($this),
            isActive: false,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasAmenity(string $amenity): bool
    {
        return in_array($amenity, $this->amenities ?? [], true);
    }
}
