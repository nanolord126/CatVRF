<?php

declare(strict_types=1);

namespace Modules\RealEstate\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\RealEstate\Domain\ValueObjects\Price;
use Modules\RealEstate\Domain\ValueObjects\PropertyId;
use Modules\RealEstate\Domain\ValueObjects\PropertyType;

final readonly class Property
{
    public function __construct(
        public readonly ?PropertyId $id,
        public readonly int $tenantId,
        public readonly PropertyType $type,
        public readonly string $title,
        public readonly string $description,
        public readonly string $address,
        public readonly string $city,
        public readonly string $country,
        public readonly Price $price,
        public readonly float $area,
        public readonly int $bedrooms,
        public readonly int $bathrooms,
        public readonly string $status,
        public readonly array $amenities,
        public readonly array $metadata,
        public readonly CarbonImmutable $createdAt,
        public readonly ?CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        PropertyType $type,
        string $title,
        string $description,
        string $address,
        string $city,
        string $country,
        Price $price,
        float $area,
        int $bedrooms,
        int $bathrooms,
        string $status = 'available',
        array $amenities = [],
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            tenantId: $tenantId,
            type: $type,
            title: $title,
            description: $description,
            address: $address,
            city: $city,
            country: $country,
            price: $price,
            area: $area,
            bedrooms: $bedrooms,
            bathrooms: $bathrooms,
            status: $status,
            amenities: $amenities,
            metadata: $metadata,
            createdAt: CarbonImmutable::now(),
            updatedAt: null,
        );
    }

    public function withId(PropertyId $id): self
    {
        return new self(
            id: $id,
            tenantId: $this->tenantId,
            type: $this->type,
            title: $this->title,
            description: $this->description,
            address: $this->address,
            city: $this->city,
            country: $this->country,
            price: $this->price,
            area: $this->area,
            bedrooms: $this->bedrooms,
            bathrooms: $this->bathrooms,
            status: $this->status,
            amenities: $this->amenities,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updatePrice(Price $newPrice): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            title: $this->title,
            description: $this->description,
            address: $this->address,
            city: $this->city,
            country: $this->country,
            price: $newPrice,
            area: $this->area,
            bedrooms: $this->bedrooms,
            bathrooms: $this->bathrooms,
            status: $this->status,
            amenities: $this->amenities,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function changeStatus(string $newStatus): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            type: $this->type,
            title: $this->title,
            description: $this->description,
            address: $this->address,
            city: $this->city,
            country: $this->country,
            price: $this->price,
            area: $this->area,
            bedrooms: $this->bedrooms,
            bathrooms: $this->bathrooms,
            status: $newStatus,
            amenities: $this->amenities,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    public function getPricePerSquareMeter(): float
    {
        return $this->area > 0 ? $this->price->value / $this->area : 0;
    }
}
