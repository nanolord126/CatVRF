<?php

declare(strict_types=1);

namespace Modules\Restaurant\Domain\Entities;

final readonly class RestaurantTable
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $zoneId,
        public string $name,
        public string $number,
        public int $capacity,
        public int $minCapacity,
        public ?string $shape, // round, square, rectangle
        public ?int $x,
        public ?int $y,
        public ?int $width,
        public ?int $height,
        public bool $isActive,
        public \Carbon\CarbonImmutable $createdAt,
        public \Carbon\CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $zoneId,
        string $name,
        string $number,
        int $capacity,
        int $minCapacity = 1,
        ?string $shape = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            zoneId: $zoneId,
            name: $name,
            number: $number,
            capacity: $capacity,
            minCapacity: $minCapacity,
            shape: $shape,
            x: null,
            y: null,
            width: null,
            height: null,
            isActive: true,
            createdAt: now()->toImmutable(),
            updatedAt: now()->toImmutable(),
        );
    }

    public function withCapacity(int $capacity): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            zoneId: $this->zoneId,
            name: $this->name,
            number: $this->number,
            capacity: $capacity,
            minCapacity: $this->minCapacity,
            shape: $this->shape,
            x: $this->x,
            y: $this->y,
            width: $this->width,
            height: $this->height,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function withPosition(int $x, int $y): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            zoneId: $this->zoneId,
            name: $this->name,
            number: $this->number,
            capacity: $this->capacity,
            minCapacity: $this->minCapacity,
            shape: $this->shape,
            x: $x,
            y: $y,
            width: $this->width,
            height: $this->height,
            isActive: $this->isActive,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function withDimensions(int $width, int $height): self
    {
        return new self(
            id: $this->id,
            tenantId: $this->tenantId,
            zoneId: $this->zoneId,
            name: $this->name,
            number: $this->number,
            capacity: $this->capacity,
            minCapacity: $this->minCapacity,
            shape: $this->shape,
            x: $this->x,
            y: $this->y,
            width: $width,
            height: $height,
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
            zoneId: $this->zoneId,
            name: $this->name,
            number: $this->number,
            capacity: $this->capacity,
            minCapacity: $this->minCapacity,
            shape: $this->shape,
            x: $this->x,
            y: $this->y,
            width: $this->width,
            height: $this->height,
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
            zoneId: $this->zoneId,
            name: $this->name,
            number: $this->number,
            capacity: $this->capacity,
            minCapacity: $this->minCapacity,
            shape: $this->shape,
            x: $this->x,
            y: $this->y,
            width: $this->width,
            height: $this->height,
            isActive: false,
            createdAt: $this->createdAt,
            updatedAt: now()->toImmutable(),
        );
    }

    public function hasPosition(): bool
    {
        return $this->x !== null && $this->y !== null;
    }
}
