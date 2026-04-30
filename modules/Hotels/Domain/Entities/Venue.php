<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Venue
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $uuid,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $address,
        public string $city,
        public string $country,
        public ?float $latitude,
        public ?float $longitude,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public int $starRating,
        public string $propertyType,
        public int $totalRooms,
        public int $totalFloors,
        public ?array $amenities,
        public ?array $checkinPolicy,
        public ?array $checkoutPolicy,
        public ?array $cancellationPolicy,
        public bool $isActive,
        public bool $isChain,
        public ?int $parentVenueId,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        string $slug,
        string $address,
        string $city,
        string $country = 'RU',
        int $starRating = 3,
        string $propertyType = 'hotel',
        int $totalRooms = 0,
        int $totalFloors = 1,
        ?string $description = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $website = null,
        ?array $amenities = null,
        ?array $checkinPolicy = null,
        ?array $checkoutPolicy = null,
        ?array $cancellationPolicy = null,
        ?int $parentVenueId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            name: $name,
            slug: $slug,
            description: $description,
            address: $address,
            city: $city,
            country: $country,
            latitude: $latitude,
            longitude: $longitude,
            phone: $phone,
            email: $email,
            website: $website,
            starRating: $starRating,
            propertyType: $propertyType,
            totalRooms: $totalRooms,
            totalFloors: $totalFloors,
            amenities: $amenities,
            checkinPolicy: $checkinPolicy,
            checkoutPolicy: $checkoutPolicy,
            cancellationPolicy: $cancellationPolicy,
            isActive: true,
            isChain: $parentVenueId !== null,
            parentVenueId: $parentVenueId,
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

    public function isHotel(): bool
    {
        return $this->propertyType === 'hotel';
    }

    public function isHostel(): bool
    {
        return $this->propertyType === 'hostel';
    }

    public function isApartment(): bool
    {
        return $this->propertyType === 'apartment';
    }
}
