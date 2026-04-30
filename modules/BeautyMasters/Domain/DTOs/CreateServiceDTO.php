<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\DTOs;

final readonly class CreateServiceDTO
{
    public function __construct(
        public int $venueId,
        public ?int $categoryId,
        public string $name,
        public string $slug,
        public ?string $description,
        public int $durationMinutes,
        public int $bufferMinutes,
        public float $price,
        public ?float $discountPrice,
        public string $currency,
        public ?array $requiredSupplies,
        public bool $requiresPhotoBefore,
        public bool $requiresPhotoAfter,
        public int $sortOrder,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            venueId: $data['venue_id'],
            categoryId: $data['category_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            durationMinutes: (int) $data['duration_minutes'],
            bufferMinutes: (int) ($data['buffer_minutes'] ?? 0),
            price: (float) $data['price'],
            discountPrice: $data['discount_price'] ? (float) $data['discount_price'] : null,
            currency: $data['currency'] ?? 'RUB',
            requiredSupplies: $data['required_supplies'] ?? null,
            requiresPhotoBefore: (bool) ($data['requires_photo_before'] ?? false),
            requiresPhotoAfter: (bool) ($data['requires_photo_after'] ?? false),
            sortOrder: (int) ($data['sort_order'] ?? 0),
        );
    }

    public function getTotalDuration(): int
    {
        return $this->durationMinutes + $this->bufferMinutes;
    }

    public function getFinalPrice(): float
    {
        return $this->discountPrice ?? $this->price;
    }

    public function toArray(): array
    {
        return [
            'venue_id' => $this->venueId,
            'category_id' => $this->categoryId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'duration_minutes' => $this->durationMinutes,
            'buffer_minutes' => $this->bufferMinutes,
            'price' => $this->price,
            'discount_price' => $this->discountPrice,
            'currency' => $this->currency,
            'required_supplies' => $this->requiredSupplies,
            'requires_photo_before' => $this->requiresPhotoBefore,
            'requires_photo_after' => $this->requiresPhotoAfter,
            'sort_order' => $this->sortOrder,
        ];
    }
}
