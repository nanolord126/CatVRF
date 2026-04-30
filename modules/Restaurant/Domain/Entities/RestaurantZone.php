<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

final readonly class RestaurantZone
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public string $name,
        public ?string $description,
        public ?string $color,
        public int $displayOrder,
        public bool $isActive,
        public int $tableCount,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        string $name,
        ?string $description = null,
        ?string $color = null,
        int $displayOrder = 0,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            color: $color,
            displayOrder: $displayOrder,
            isActive: true,
            tableCount: 0,
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
            color: $this->color,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            tableCount: $this->tableCount,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function withTableCount(int $count): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            color: $this->color,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            tableCount: $count,
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
            color: $this->color,
            displayOrder: $this->displayOrder,
            isActive: true,
            tableCount: $this->tableCount,
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
            color: $this->color,
            displayOrder: $this->displayOrder,
            isActive: false,
            tableCount: $this->tableCount,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }
}
