<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\ValueObjects\Money;

final readonly class MenuItem
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $categoryId,
        public string $name,
        public ?string $description,
        public Money $price,
        public ?string $imageUrl,
        public string $sku,
        public int $preparationTime, // в минутах
        public bool $isActive,
        public bool $isAvailable,
        public bool $isFeatured,
        public ?string $allergens,
        public ?string $nutritionalInfo,
        public int $calories,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $categoryId,
        string $name,
        Money $price,
        ?string $description = null,
        string $sku = '',
        int $preparationTime = 15,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            categoryId: $categoryId,
            name: $name,
            description: $description,
            price: $price,
            imageUrl: null,
            sku: $sku,
            preparationTime: $preparationTime,
            isActive: true,
            isAvailable: true,
            isFeatured: false,
            allergens: null,
            nutritionalInfo: null,
            calories: 0,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function withPrice(Money $price): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: $this->isActive,
            isAvailable: $this->isAvailable,
            isFeatured: $this->isFeatured,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: true,
            isAvailable: $this->isAvailable,
            isFeatured: $this->isFeatured,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: false,
            isAvailable: false,
            isFeatured: $this->isFeatured,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function markAsAvailable(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: $this->isActive,
            isAvailable: true,
            isFeatured: $this->isFeatured,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function markAsUnavailable(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: $this->isActive,
            isAvailable: false,
            isFeatured: $this->isFeatured,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function markAsFeatured(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: $this->isActive,
            isAvailable: $this->isAvailable,
            isFeatured: true,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function unmarkAsFeatured(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            categoryId: $this->categoryId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            imageUrl: $this->imageUrl,
            sku: $this->sku,
            preparationTime: $this->preparationTime,
            isActive: $this->isActive,
            isAvailable: $this->isAvailable,
            isFeatured: false,
            allergens: $this->allergens,
            nutritionalInfo: $this->nutritionalInfo,
            calories: $this->calories,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }
}
