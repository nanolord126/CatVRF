<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

final readonly class MenuCategory
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public ?string $imageUrl,
        public int $displayOrder,
        public bool $isActive,
        public ?int $parentId,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        ?string $description = null,
        ?string $imageUrl = null,
        int $displayOrder = 0,
        ?int $parentId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            imageUrl: $imageUrl,
            displayOrder: $displayOrder,
            isActive: true,
            parentId: $parentId,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $name,
            description: $this->description,
            imageUrl: $this->imageUrl,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            parentId: $this->parentId,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function withDisplayOrder(int $displayOrder): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            imageUrl: $this->imageUrl,
            displayOrder: $displayOrder,
            isActive: $this->isActive,
            parentId: $this->parentId,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            imageUrl: $this->imageUrl,
            displayOrder: $this->displayOrder,
            isActive: true,
            parentId: $this->parentId,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            imageUrl: $this->imageUrl,
            displayOrder: $this->displayOrder,
            isActive: false,
            parentId: $this->parentId,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function isSubcategory(): bool
    {
        return $this->parentId !== null;
    }
}
