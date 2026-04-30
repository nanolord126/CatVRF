<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final readonly class Service
{
    public function __construct(
        public int $id,
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
        public bool $isActive,
        public bool $requiresPhotoBefore,
        public bool $requiresPhotoAfter,
        public int $sortOrder,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            venueId: $data['venue_id'],
            categoryId: $data['category_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'],
            description: $data['description'] ?? null,
            durationMinutes: (int) $data['duration_minutes'],
            bufferMinutes: (int) $data['buffer_minutes'],
            price: (float) $data['price'],
            discountPrice: $data['discount_price'] ? (float) $data['discount_price'] : null,
            currency: $data['currency'] ?? 'RUB',
            requiredSupplies: $data['required_supplies'] ?? null,
            isActive: (bool) $data['is_active'],
            requiresPhotoBefore: (bool) $data['requires_photo_before'],
            requiresPhotoAfter: (bool) $data['requires_photo_after'],
            sortOrder: (int) $data['sort_order'],
            metadata: $data['metadata'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new \DateTimeImmutable($data['updated_at']) : null,
            deletedAt: $data['deleted_at'] ? new \DateTimeImmutable($data['deleted_at']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
            'is_active' => $this->isActive,
            'requires_photo_before' => $this->requiresPhotoBefore,
            'requires_photo_after' => $this->requiresPhotoAfter,
            'sort_order' => $this->sortOrder,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function getTotalDuration(): int
    {
        return $this->durationMinutes + $this->bufferMinutes;
    }

    public function getFinalPrice(): float
    {
        return $this->discountPrice ?? $this->price;
    }
}
