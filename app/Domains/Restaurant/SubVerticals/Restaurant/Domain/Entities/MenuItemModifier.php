<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

use Modules\Restaurant\Domain\ValueObjects\Money;

final readonly class MenuItemModifier
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $menuItemId,
        public string $name,
        public ?string $description,
        public Money $price,
        public bool $isRequired,
        public bool $isMultiSelect,
        public int $maxSelectCount,
        public int $displayOrder,
        public bool $isActive,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $menuItemId,
        string $name,
        Money $price,
        bool $isRequired = false,
        bool $isMultiSelect = false,
        int $maxSelectCount = 1,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            menuItemId: $menuItemId,
            name: $name,
            description: null,
            price: $price,
            isRequired: $isRequired,
            isMultiSelect: $isMultiSelect,
            maxSelectCount: $maxSelectCount,
            displayOrder: 0,
            isActive: true,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function withPrice(Money $price): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            menuItemId: $this->menuItemId,
            name: $this->name,
            description: $this->description,
            price: $price,
            isRequired: $this->isRequired,
            isMultiSelect: $this->isMultiSelect,
            maxSelectCount: $this->maxSelectCount,
            displayOrder: $this->displayOrder,
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
            name: $this->name,
            description: $this->description,
            price: $this->price,
            isRequired: $this->isRequired,
            isMultiSelect: $this->isMultiSelect,
            maxSelectCount: $this->maxSelectCount,
            displayOrder: $this->displayOrder,
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
            name: $this->name,
            description: $this->description,
            price: $this->price,
            isRequired: $this->isRequired,
            isMultiSelect: $this->isMultiSelect,
            maxSelectCount: $this->maxSelectCount,
            displayOrder: $this->displayOrder,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function setRequired(bool $required): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            menuItemId: $this->menuItemId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            isRequired: $required,
            isMultiSelect: $this->isMultiSelect,
            maxSelectCount: $this->maxSelectCount,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function setMultiSelect(bool $multiSelect, int $maxCount = 1): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            menuItemId: $this->menuItemId,
            name: $this->name,
            description: $this->description,
            price: $this->price,
            isRequired: $this->isRequired,
            isMultiSelect: $multiSelect,
            maxSelectCount: $maxCount,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }
}
