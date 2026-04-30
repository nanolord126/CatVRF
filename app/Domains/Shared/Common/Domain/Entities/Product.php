<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Entities;

use App\Domains\Common\Domain\ValueObjects\Price;
use App\Domains\Common\Domain\ValueObjects\Quantity;
use App\Domains\Common\Domain\ValueObjects\SKU;
use Carbon\CarbonImmutable;

final readonly class Product
{
    public function __construct(
        public int $id,
        public SKU $sku,
        public string $name,
        public ?string $description,
        public Price $price,
        public Quantity $stock,
        public ?int $categoryId,
        public ?int $brandId,
        public bool $isActive,
        public bool $isFeatured,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        SKU $sku,
        string $name,
        ?string $description,
        Price $price,
        Quantity $stock,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?array $metadata = null,
    ): self {
        $now = CarbonImmutable::now();

        return new self(
            id: 0,
            sku: $sku,
            name: $name,
            description: $description,
            price: $price,
            stock: $stock,
            categoryId: $categoryId,
            brandId: $brandId,
            isActive: true,
            isFeatured: false,
            metadata: $metadata,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function withStock(Quantity $stock): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            stock: $stock,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            isActive: $this->isActive,
            isFeatured: $this->isFeatured,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withPrice(Price $price): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            price: $price,
            stock: $this->stock,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            isActive: $this->isActive,
            isFeatured: $this->isFeatured,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            stock: $this->stock,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            isActive: true,
            isFeatured: $this->isFeatured,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            stock: $this->stock,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            isActive: false,
            isFeatured: $this->isFeatured,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function feature(): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            stock: $this->stock,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            isActive: $this->isActive,
            isFeatured: true,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function unfeature(): self
    {
        return new self(
            id: $this->id,
            sku: $this->sku,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            stock: $this->stock,
            categoryId: $this->categoryId,
            brandId: $this->brandId,
            isActive: $this->isActive,
            isFeatured: false,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isInStock(): bool
    {
        return $this->stock->isPositive();
    }

    public function isOutOfStock(): bool
    {
        return $this->stock->isZero();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku->value,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price->toArray(),
            'stock' => $this->stock->toArray(),
            'category_id' => $this->categoryId,
            'brand_id' => $this->brandId,
            'is_active' => $this->isActive,
            'is_featured' => $this->isFeatured,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
