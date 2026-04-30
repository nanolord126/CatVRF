<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

final readonly class Product
{
    public function __construct(
        public int $id,
        public int $venueId,
        public ?int $categoryId,
        public string $name,
        public string $slug,
        public ?string $description,
        public ?string $sku,
        public ?string $brand,
        public float $purchasePrice,
        public float $salePrice,
        public ?float $discountPrice,
        public string $currency,
        public int $quantityInStock,
        public int $reorderLevel,
        public string $unit,
        public ?array $images,
        public bool $isActive,
        public bool $isSupply,
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
            sku: $data['sku'] ?? null,
            brand: $data['brand'] ?? null,
            purchasePrice: (float) $data['purchase_price'],
            salePrice: (float) $data['sale_price'],
            discountPrice: $data['discount_price'] ? (float) $data['discount_price'] : null,
            currency: $data['currency'] ?? 'RUB',
            quantityInStock: (int) $data['quantity_in_stock'],
            reorderLevel: (int) $data['reorder_level'],
            unit: $data['unit'] ?? 'pcs',
            images: $data['images'] ?? null,
            isActive: (bool) $data['is_active'],
            isSupply: (bool) $data['is_supply'],
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
            'sku' => $this->sku,
            'brand' => $this->brand,
            'purchase_price' => $this->purchasePrice,
            'sale_price' => $this->salePrice,
            'discount_price' => $this->discountPrice,
            'currency' => $this->currency,
            'quantity_in_stock' => $this->quantityInStock,
            'reorder_level' => $this->reorderLevel,
            'unit' => $this->unit,
            'images' => $this->images,
            'is_active' => $this->isActive,
            'is_supply' => $this->isSupply,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deletedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function getFinalPrice(): float
    {
        return $this->discountPrice ?? $this->salePrice;
    }

    public function isLowStock(): bool
    {
        return $this->quantityInStock <= $this->reorderLevel;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantityInStock <= 0;
    }

    public function getProfitMargin(): float
    {
        if ($this->purchasePrice === 0.0) {
            return 0.0;
        }
        return (($this->getFinalPrice() - $this->purchasePrice) / $this->purchasePrice) * 100;
    }
}
