<?php

declare(strict_types=1);

namespace App\Domains\ToysAndGames\Toys\DTOs;

final readonly class ToyAIRequestDto
{
    public function __construct(
        public int $userId,
        public int $ageMonths,
        public array $interests,
        public int $budgetLimit,
        public bool $educationalOnly = false,
        public bool $b2bMode = false,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'age_months' => $this->ageMonths,
            'interests' => $this->interests,
            'budget_limit' => $this->budgetLimit,
            'educational_only' => $this->educationalOnly,
            'b2b_mode' => $this->b2bMode,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            ageMonths: $data['age_months'],
            interests: $data['interests'],
            budgetLimit: $data['budget_limit'],
            educationalOnly: $data['educational_only'] ?? false,
            b2bMode: $data['b2b_mode'] ?? false,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
