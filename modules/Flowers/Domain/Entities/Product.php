<?php

declare(strict_types=1);

namespace Modules\Flowers\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Product
{
    public function __construct(
        public int $id,
        public int $venueId,
        public int $tenantId,
        public string $name,
        public string $slug,
        public ?string $description,
        public string $category,
        public string $size,
        public float $basePrice,
        public ?float $discountPrice,
        public string $currency,
        public ?string $mainImage,
        public ?array $galleryImages,
        public ?string $compositionNotes,
        public int $preparationTimeMinutes,
        public bool $isSeasonal,
        public bool $isFeatured,
        public bool $isActive,
        public int $sortOrder,
        public ?array $seoData,
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
        string $category,
        float $basePrice,
        string $size = 'm',
        ?string $description = null,
        int $preparationTimeMinutes = 30,
        ?string $mainImage = null,
    ): self {
        return new self(
            id: 0,
            venueId: $venueId,
            tenantId: $tenantId,
            name: $name,
            slug: $slug,
            description: $description,
            category: $category,
            size: $size,
            basePrice: $basePrice,
            discountPrice: null,
            currency: 'RUB',
            mainImage: $mainImage,
            galleryImages: null,
            compositionNotes: null,
            preparationTimeMinutes: $preparationTimeMinutes,
            isSeasonal: false,
            isFeatured: false,
            isActive: true,
            sortOrder: 0,
            seoData: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function getCurrentPrice(): float
    {
        return $this->discountPrice ?? $this->basePrice;
    }

    public function hasDiscount(): bool
    {
        return $this->discountPrice !== null && $this->discountPrice < $this->basePrice;
    }

    public function getDiscountPercentage(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((($this->basePrice - $this->discountPrice) / $this->basePrice) * 100);
    }
}
