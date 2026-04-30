<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

/**
 * CreateTrainingCourseDTO — DTO для создания курса обучения
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class CreateTrainingCourseDTO
{
    public function __construct(
        public int $tenantId,
        public string $title,
        public string $description,
        public string $category,
        public int $durationHours,
        public int $difficultyLevel,
        public array $requiredSkills,
        public array $acquiredSkills,
        public int $pointsReward,
        public ?string $externalUrl = null,
        public bool $isActive = true,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            title: $data['title'],
            description: $data['description'],
            category: $data['category'],
            durationHours: $data['duration_hours'],
            difficultyLevel: $data['difficulty_level'],
            requiredSkills: $data['required_skills'] ?? [],
            acquiredSkills: $data['acquired_skills'] ?? [],
            pointsReward: $data['points_reward'],
            externalUrl: $data['external_url'] ?? null,
            isActive: $data['is_active'] ?? true,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'duration_hours' => $this->durationHours,
            'difficulty_level' => $this->difficultyLevel,
            'required_skills' => $this->requiredSkills,
            'acquired_skills' => $this->acquiredSkills,
            'points_reward' => $this->pointsReward,
            'external_url' => $this->externalUrl,
            'is_active' => $this->isActive,
            'metadata' => $this->metadata,
        ];
    }
}
