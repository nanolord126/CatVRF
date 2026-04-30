<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

/**
 * CreateBadgeDTO — DTO для создания бейджа
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateBadgeDTO
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $description,
        public string $icon,
        public string $category,
        public int $pointsRequired,
        public string $condition,
        public bool $isRare = false,
        public bool $isLegendary = false,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            description: $data['description'],
            icon: $data['icon'],
            category: $data['category'],
            pointsRequired: $data['points_required'],
            condition: $data['condition'],
            isRare: $data['is_rare'] ?? false,
            isLegendary: $data['is_legendary'] ?? false,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'category' => $this->category,
            'points_required' => $this->pointsRequired,
            'condition' => $this->condition,
            'is_rare' => $this->isRare,
            'is_legendary' => $this->isLegendary,
            'metadata' => $this->metadata,
        ];
    }
}
