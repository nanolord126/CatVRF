<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

final readonly class Recipe
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $menuItemId,
        public int $ingredientId,
        public float $quantity,
        public string $unit,
        public bool $isActive,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $menuItemId,
        int $ingredientId,
        float $quantity,
        string $unit,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            menuItemId: $menuItemId,
            ingredientId: $ingredientId,
            quantity: $quantity,
            unit: $unit,
            isActive: true,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function withQuantity(float $quantity): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            menuItemId: $this->menuItemId,
            ingredientId: $this->ingredientId,
            quantity: $quantity,
            unit: $this->unit,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            menuItemId: $this->menuItemId,
            ingredientId: $this->ingredientId,
            quantity: $this->quantity,
            unit: $this->unit,
            isActive: true,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            menuItemId: $this->menuItemId,
            ingredientId: $this->ingredientId,
            quantity: $this->quantity,
            unit: $this->unit,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }
}
