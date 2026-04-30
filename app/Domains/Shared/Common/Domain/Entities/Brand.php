<?php

declare(strict_types=1);

namespace App\Domains\Common\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class Brand
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $slug,
        public ?string $logo,
        public ?string $website,
        public ?string $description,
        public bool $isActive,
        public bool $isVerified,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        string $name,
        ?string $slug = null,
        ?string $logo = null,
        ?string $website = null,
        ?string $description = null,
        ?array $metadata = null,
    ): self {
        $now = CarbonImmutable::now();

        return new self(
            id: 0,
            name: $name,
            slug: $slug ?? self::generateSlug($name),
            logo: $logo,
            website: $website,
            description: $description,
            isActive: true,
            isVerified: false,
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
            logo: $this->logo,
            website: $this->website,
            description: $this->description,
            isActive: true,
            isVerified: $this->isVerified,
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
            logo: $this->logo,
            website: $this->website,
            description: $this->description,
            isActive: false,
            isVerified: $this->isVerified,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function verify(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            logo: $this->logo,
            website: $this->website,
            description: $this->description,
            isActive: $this->isActive,
            isVerified: true,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function unverify(): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            slug: $this->slug,
            logo: $this->logo,
            website: $this->website,
            description: $this->description,
            isActive: $this->isActive,
            isVerified: false,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => $this->logo,
            'website' => $this->website,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt->toIso8601String(),
            'updated_at' => $this->updatedAt->toIso8601String(),
        ];
    }
}
