<?php

declare(strict_types=1);

namespace Modules\Hotels\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class RoomType
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $venueId,
        public string $uuid,
        public string $name,
        public string $code,
        public ?string $description,
        public int $maxOccupancy,
        public int $maxAdults,
        public int $maxChildren,
        public int $numberOfBeds,
        public ?string $bedConfiguration,
        public ?float $areaSqm,
        public ?array $amenities,
        public ?array $photos,
        public bool $isActive,
        public int $sortOrder,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $venueId,
        string $name,
        string $code,
        int $maxOccupancy = 2,
        int $maxAdults = 2,
        int $maxChildren = 0,
        int $numberOfBeds = 1,
        ?string $description = null,
        ?string $bedConfiguration = null,
        ?float $areaSqm = null,
        ?array $amenities = null,
        ?array $photos = null,
        int $sortOrder = 0,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            venueId: $venueId,
            uuid: (string) \Illuminate\Support\Str::uuid(),
            name: $name,
            code: $code,
            description: $description,
            maxOccupancy: $maxOccupancy,
            maxAdults: $maxAdults,
            maxChildren: $maxChildren,
            numberOfBeds: $numberOfBeds,
            bedConfiguration: $bedConfiguration,
            areaSqm: $areaSqm,
            amenities: $amenities,
            photos: $photos,
            isActive: true,
            sortOrder: $sortOrder,
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

    public function canAccommodate(int $adults, int $children = 0): bool
    {
        return $this->maxAdults >= $adults && $this->maxChildren >= $children;
    }
}
