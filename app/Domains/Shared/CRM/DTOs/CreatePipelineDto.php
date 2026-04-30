<?php

declare(strict_types=1);

namespace App\Domains\CRM\DTOs;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * CreatePipelineDto — DTO для создания воронки.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final readonly class CreatePipelineDto implements Castable
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $name,
        public string $slug,
        public string $vertical,
        public ?string $description = null,
        public bool $isDefault = false,
        public bool $isActive = true,
        public ?string $color = null,
        public ?string $icon = null,
        public int $order = 0,
        public ?array $metadata = null,
        public ?string $correlationId = null,
    ) {}

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class implements CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes): ?CreatePipelineDto
            {
                if ($value === null) {
                    return null;
                }

                $data = json_decode($value, true);

                return new CreatePipelineDto(
                    tenantId: $data['tenantId'],
                    businessGroupId: $data['businessGroupId'] ?? null,
                    name: $data['name'],
                    slug: $data['slug'],
                    vertical: $data['vertical'],
                    description: $data['description'] ?? null,
                    isDefault: $data['isDefault'] ?? false,
                    isActive: $data['isActive'] ?? true,
                    color: $data['color'] ?? null,
                    icon: $data['icon'] ?? null,
                    order: $data['order'] ?? 0,
                    metadata: $data['metadata'] ?? null,
                    correlationId: $data['correlationId'] ?? null,
                );
            }

            public function set($model, string $key, $value, array $attributes): string
            {
                return json_encode([
                    'tenantId' => $value->tenantId,
                    'businessGroupId' => $value->businessGroupId,
                    'name' => $value->name,
                    'slug' => $value->slug,
                    'vertical' => $value->vertical,
                    'description' => $value->description,
                    'isDefault' => $value->isDefault,
                    'isActive' => $value->isActive,
                    'color' => $value->color,
                    'icon' => $value->icon,
                    'order' => $value->order,
                    'metadata' => $value->metadata,
                    'correlationId' => $value->correlationId,
                ]);
            }
        };
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'name' => $this->name,
            'slug' => $this->slug,
            'vertical' => $this->vertical,
            'description' => $this->description,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
            'color' => $this->color,
            'icon' => $this->icon,
            'order' => $this->order,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            businessGroupId: $data['business_group_id'] ?? null,
            name: $data['name'],
            slug: $data['slug'],
            vertical: $data['vertical'],
            description: $data['description'] ?? null,
            isDefault: $data['is_default'] ?? false,
            isActive: $data['is_active'] ?? true,
            color: $data['color'] ?? null,
            icon: $data['icon'] ?? null,
            order: $data['order'] ?? 0,
            metadata: $data['metadata'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
