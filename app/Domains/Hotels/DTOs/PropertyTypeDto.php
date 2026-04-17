<?php

declare(strict_types=1);

namespace App\Domains\Hotels\DTOs;

use Illuminate\Http\Request;

/**
 * PropertyTypeDto — DTO для типа размещения.
 *
 * CatVRF 9-layer architecture — Layer 2: DTOs.
 *
 * @package App\Domains\Hotels\DTOs
 */
final readonly class PropertyTypeDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $slug,
        public string $name,
        public string $nameRu,
        public ?string $description,
        public ?string $icon,
        public bool $isActive,
        public int $sortOrder,
        public ?int $minStars,
        public ?int $maxStars,
        public ?array $features,
        public ?array $metadata,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(Request $request, int $tenantId): self
    {
        return new self(
            tenantId: $tenantId,
            businessGroupId: $request->input('business_group_id') ? (int) $request->input('business_group_id') : null,
            slug: (string) $request->input('slug'),
            name: (string) $request->input('name'),
            nameRu: (string) $request->input('name_ru'),
            description: $request->input('description'),
            icon: $request->input('icon'),
            isActive: (bool) $request->input('is_active', true),
            sortOrder: (int) $request->input('sort_order', 0),
            minStars: $request->input('min_stars') ? (int) $request->input('min_stars') : null,
            maxStars: $request->input('max_stars') ? (int) $request->input('max_stars') : null,
            features: $request->input('features'),
            metadata: $request->input('metadata'),
            correlationId: $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            idempotencyKey: $request->header('Idempotency-Key'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'slug' => $this->slug,
            'name' => $this->name,
            'name_ru' => $this->nameRu,
            'description' => $this->description,
            'icon' => $this->icon,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
            'min_stars' => $this->minStars,
            'max_stars' => $this->maxStars,
            'features' => $this->features,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
        ];
    }
}
