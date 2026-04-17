<?php declare(strict_types=1);

namespace App\Domains\Education\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domains\Education\DTOs\LearningPathRecommendationDto;

final class LearningPathResource extends JsonResource
{
    public function toArray($request): array
    {
        $recommendation = $this->resource instanceof LearningPathRecommendationDto 
            ? $this->resource 
            : LearningPathRecommendationDto::fromArray($this->resource);

        return [
            'path_id' => $recommendation->pathId,
            'modules' => $recommendation->modules,
            'estimated_hours' => $recommendation->estimatedHours,
            'estimated_weeks' => $recommendation->estimatedWeeks,
            'difficulty_level' => $recommendation->difficultyLevel,
            'adaptive_adjustments' => $recommendation->adaptiveAdjustments,
            'recommended_resources' => $recommendation->recommendedResources,
            'completion_probability' => round($recommendation->completionProbability * 100, 1) . '%',
            'milestones' => $recommendation->milestones,
            'generated_at' => $recommendation->generatedAt,
        ];
    }
}
