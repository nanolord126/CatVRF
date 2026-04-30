<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Modifier
{
    public function __construct(
        public int $id,
        public int $venueId,
        public int $tenantId,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $type,
        public float $price,
        public string $currency,
        public ?string $image,
        public bool $isActive,
        public int $sortOrder,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $venueId,
        int $tenantId,
        string $name,
        string $slug,
        string $type,
        float $price,
        ?string $description = null,
        ?string $image = null,
    ): self {
        return new self(
            id: 0,
            venueId: $venueId,
            tenantId: $tenantId,
            name: $name,
            slug: $slug,
            description: $description,
            type: $type,
            price: $price,
            currency: 'RUB',
            image: $image,
            isActive: true,
            sortOrder: 0,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }
}
