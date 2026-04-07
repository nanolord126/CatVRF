<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\DTOs;

/**
     * AIVeganConstructorRequestDto - Input for the AI Box & Menu Constructor.
     */
final readonly class AIVeganConstructorRequestDto
{
        public function __construct(
            public int $userId,
            public string $dietGoal, // muscle_gain, weight_loss, maintain
            private array $allergies = [],
            private int $budgetLimitCop = 0,
            private int $servingsPerDay = 3,
            private array $favorites = [],
            private ?string $correlationId = null) {}
}
