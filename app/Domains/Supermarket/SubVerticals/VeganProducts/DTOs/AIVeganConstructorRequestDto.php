<?php

declare(strict_types=1);

namespace App\Domains\Supermarket\SubVerticals\VeganProducts\DTOs;

final readonly class AIVeganConstructorRequestDto
{
    public function __construct(
        public int $userId,
        public string $dietGoal,
        public array $allergies = [],
        public int $budgetLimitCop = 0,
        public int $servingsPerDay = 3,
        public array $favorites = [],
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'diet_goal' => $this->dietGoal,
            'allergies' => $this->allergies,
            'budget_limit_cop' => $this->budgetLimitCop,
            'servings_per_day' => $this->servingsPerDay,
            'favorites' => $this->favorites,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            userId: $data['user_id'],
            dietGoal: $data['diet_goal'],
            allergies: $data['allergies'] ?? [],
            budgetLimitCop: $data['budget_limit_cop'] ?? 0,
            servingsPerDay: $data['servings_per_day'] ?? 3,
            favorites: $data['favorites'] ?? [],
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
