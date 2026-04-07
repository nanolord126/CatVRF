<?php

declare(strict_types=1);

namespace App\Domains\SportsNutrition\DTOs;

/**
     * AISupplementRequestDto (Layer 2/9)
     * Input for the AI Constructor to analyze user needs.
     */
final readonly class AISupplementRequestDto
{
        public function __construct(
            public int $user_id,
            public string $goal, // 'bulking', 'cutting', 'recovery', 'endurance'
            public float $weight_kg,
            public int $age,
            public string $dietary_restriction, // 'vegan', 'keto', 'no-dairy'
            public array $active_training_days, // ['Mon', 'Wed', 'Fri']
            private int $budget_kopecks_max = 1000000
        ) {}
    }
