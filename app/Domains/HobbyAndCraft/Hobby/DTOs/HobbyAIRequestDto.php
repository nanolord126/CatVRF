<?php

declare(strict_types=1);

namespace App\Domains\HobbyAndCraft\Hobby\DTOs;

/**
     * HobbyAIRequestDto
     * Payload for the AI Constructor to match materials and kits to user skills.
     */
final readonly class HobbyAIRequestDto
{
        public function __construct(
            public int $userId,
            public string $skillLevel,
            public array $interests,
            public int $budgetLimit,
            private bool $includeTutorials = true,
            private bool $b2bMode = false,
            private ?string $correlationId = null
        ) {}

        public static function fromRequest(\Illuminate\Http\Request $request): self
        {
            return new self(
                userId: (int) ($request->user()?->id ?? 0),
                skillLevel: (string) $request->get('skill_level', 'beginner'),
                interests: (array) $request->get('interests', []),
                budgetLimit: (int) ($request->get('budget_limit', 1000000)), // 10k default limit
                includeTutorials: (bool) $request->get('include_tutorials', true),
                b2bMode: (bool) $request->get('b2b_mode', false),
                correlationId: $request->header('X-Correlation-ID')
            );
        }
    }
