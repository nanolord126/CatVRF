<?php declare(strict_types=1);

namespace App\Domains\Education\DTOs;

final readonly class LearningPathRecommendationDto
{
    public function __construct(
        public string $pathId,
        public array $modules,
        public int $estimatedHours,
        public int $estimatedWeeks,
        public string $difficultyLevel,
        public array $adaptiveAdjustments,
        public array $recommendedResources,
        public float $completionProbability,
        public array $milestones,
        public string $generatedAt,
    ) {}

    public function toArray(): array
    {
        return [
            'path_id' => $this->pathId,
            'modules' => $this->modules,
            'estimated_hours' => $this->estimatedHours,
            'estimated_weeks' => $this->estimatedWeeks,
            'difficulty_level' => $this->difficultyLevel,
            'adaptive_adjustments' => $this->adaptiveAdjustments,
            'recommended_resources' => $this->recommendedResources,
            'completion_probability' => $this->completionProbability,
            'milestones' => $this->milestones,
            'generated_at' => $this->generatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            pathId: $data['path_id'],
            modules: $data['modules'],
            estimatedHours: $data['estimated_hours'],
            estimatedWeeks: $data['estimated_weeks'],
            difficultyLevel: $data['difficulty_level'],
            adaptiveAdjustments: $data['adaptive_adjustments'],
            recommendedResources: $data['recommended_resources'],
            completionProbability: $data['completion_probability'],
            milestones: $data['milestones'],
            generatedAt: $data['generated_at'],
        );
    }
}
