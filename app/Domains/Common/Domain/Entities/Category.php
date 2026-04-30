<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug,
        public ?string $description,
        public ?int $parentId,
        public int $ sortOrder,
        public bool $isActive,
        public ?string $icon,
        public ?string $image,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?int $parentId = null,
        int $sortOrder = 0,
        ?string $icon = null,
        ?string $image = null,
        ?array $metadata = null,
    ): self {
        $now = CarbonImmutable::now();

        return new self(
            id: 0,
            name: $name,
            slug: $slug ?? self::generateSlug($name),
            description: $description,
            parentId: $parentId,
            sortOrder: $sortOrder,
            isActive: true,
            icon: $icon,
            image: $image,
            metadata: $metadata,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    private static function generateSlug(string $name): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            parentId: $this->parentId,
            sortOrder: $this->sortOrder,
            isActive: true,
            icon: $this->icon,
            image: $this->image,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            parentId: $this->parentId,
            sortOrder: $this->sortOrder,
            isActive: false,
            icon: $this->icon,
            image: $this->image,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withParent(?int $parentId): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            parentId: $parentId,
            sortOrder: $this->sortOrder,
            isActive: $this->isActive,
            icon: $this->icon,
            image: $this->image,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function withSortOrder(int $sortOrder): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            description: $this->description,
            parentId: $this->parentId,
            sortOrder: $sortOrder,
            isActive: $this->isActive,
            icon: $this->icon,
            image: $this->image,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }

    public function hasParent(): bool
    {
        return $this->parentId !== null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'icon' => $this->icon,
            'image' => $this->image,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
