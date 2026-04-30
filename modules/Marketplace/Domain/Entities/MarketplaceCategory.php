<?php

declare(strict_types=1);

namespace Modules\Marketplace\Domain\Entities;

use Modules\Marketplace\Domain\ValueObjects\VerticalSource;
use Ramsey\Uuid\UuidInterface;

/**
 * Категория маркетплейса для агрегации товаров из разных вертикалей
 */
final readonly class MarketplaceCategory
{
    private function __construct(
        public UuidInterface $uuid,
        public string $slug,
        public string $name,
        public ?string $description,
        public ?UuidInterface $parentUuid,
        public int $level,
        public string $path,
        public array $verticals,
        public array $attributes,
        public bool $isActive,
        public int $sortOrder,
        public ?string $icon,
        public ?string $image,
        public array $metadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
        $this->validate();
    }

    public static function create(
        string $slug,
        string $name,
        ?string $description = null,
        ?UuidInterface $parentUuid = null,
        array $verticals = [],
        array $attributes = [],
        array $metadata = [],
    ): self {
        $level = $parentUuid === null ? 1 : 2;
        $path = $parentUuid === null ? $slug : ''; // Will be set by parent

        return new self(
            uuid: \Ramsey\Uuid\Uuid::uuid4(),
            slug: $slug,
            name: $name,
            description: $description,
            parentUuid: $parentUuid,
            level: $level,
            path: $path,
            verticals: $verticals,
            attributes: $attributes,
            isActive: true,
            sortOrder: 0,
            icon: null,
            image: null,
            metadata: $metadata,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            uuid: \Ramsey\Uuid\Uuid::fromString($data['uuid']),
            slug: $data['slug'],
            name: $data['name'],
            description: $data['description'] ?? null,
            parentUuid: isset($data['parent_uuid']) ? \Ramsey\Uuid\Uuid::fromString($data['parent_uuid']) : null,
            level: (int) $data['level'],
            path: $data['path'],
            verticals: (array) $data['verticals'],
            attributes: (array) $data['attributes'],
            isActive: (bool) $data['is_active'],
            sortOrder: (int) $data['sort_order'],
            icon: $data['icon'] ?? null,
            image: $data['image'] ?? null,
            metadata: (array) $data['metadata'],
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: new \DateTimeImmutable($data['updated_at']),
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid->toString(),
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'parent_uuid' => $this->parentUuid?->toString(),
            'level' => $this->level,
            'path' => $this->path,
            'verticals' => $this->verticals,
            'attributes' => $this->attributes,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'icon' => $this->icon,
            'image' => $this->image,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function withPath(string $path): self
    {
        return new self(
            ...$this->toArray(),
            path: $path,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withParent(UuidInterface $parentUuid, string $parentPath): self
    {
        return new self(
            ...$this->toArray(),
            parentUuid: $parentUuid,
            level: $this->level + 1,
            path: $parentPath . '/' . $this->slug,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withVertical(VerticalSource $vertical): self
    {
        $verticals = $this->verticals;
        if (!in_array($vertical->value, $verticals, true)) {
            $verticals[] = $vertical->value;
        }

        return new self(
            ...$this->toArray(),
            verticals: $verticals,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withoutVertical(VerticalSource $vertical): self
    {
        $verticals = array_filter($this->verticals, fn($v) => $v !== $vertical->value);
        return new self(
            ...$this->toArray(),
            verticals: array_values($verticals),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function activate(): self
    {
        return new self(
            ...$this->toArray(),
            isActive: true,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function deactivate(): self
    {
        return new self(
            ...$this->toArray(),
            isActive: false,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function withSortOrder(int $sortOrder): self
    {
        return new self(
            ...$this->toArray(),
            sortOrder: $sortOrder,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function hasVertical(VerticalSource $vertical): bool
    {
        return in_array($vertical->value, $this->verticals, true);
    }

    public function isRoot(): bool
    {
        return $this->parentUuid === null;
    }

    public function isLeaf(): bool
    {
        return $this->level >= 3; // Assuming max 3 levels
    }

    public function supportsVertical(VerticalSource $vertical): bool
    {
        return in_array($vertical->value, $this->verticals, true);
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    private function validate(): void
    {
        if (empty($this->slug)) {
            throw new \InvalidArgumentException('Slug cannot be empty');
        }

        if (!preg_match('/^[a-z0-9-]+$/', $this->slug)) {
            throw new \InvalidArgumentException('Slug must contain only lowercase letters, numbers, and hyphens');
        }

        if (empty($this->name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if ($this->level < 1 || $this->level > 5) {
            throw new \InvalidArgumentException('Level must be between 1 and 5');
        }

        if ($this->sortOrder < 0) {
            throw new \InvalidArgumentException('Sort order cannot be negative');
        }
    }
}
