<?php

declare(strict_types=1);

namespace App\Domains\SportsNutrition\DTOs;

/**
     * AISupplementResultDto (Layer 2/9)
     * Orchestrated result from the AI Layer.
     */
final readonly class AISupplementResultDto
{
        public function __construct(
            public string $vertical,
            public string $recommended_stack_name,
            public array $payload, // [calories => 3000, protein => 200]
            public \Illuminate\Support\Collection $suggestions, // Collection of SportsNutritionProduct models
            public float $confidence_score,
            public string $correlation_id
        ) {}
    }
