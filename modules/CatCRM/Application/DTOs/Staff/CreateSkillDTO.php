<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

/**
 * CreateSkillDTO — DTO для создания навыка
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateSkillDTO
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public string $category,
        public string $description,
        public string $level,
        public int $proficiency,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            name: $data['name'],
            category: $data['category'],
            description: $data['description'],
            level: $data['level'],
            proficiency: $data['proficiency'],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'level' => $this->level,
            'proficiency' => $this->proficiency,
            'metadata' => $this->metadata,
        ];
    }
}
