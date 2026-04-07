<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\DTOs;

/**
     * ToyAIRequestDto (Layer 2)
     * Input for the AI Toy & Game Constructor.
     * Features: context-sensitive matching for kids' interests.
     */
final readonly class ToyAIRequestDto
{
        public function __construct(
            public int $userId,
            public int $ageMonths,
            public array $interests, // e.g., ['space', 'dinosaurs', 'coding']
            public int $budgetLimit, // in kopecks
            private bool $educationalOnly = false,
            private bool $b2bMode = false
        ) {}
    }
