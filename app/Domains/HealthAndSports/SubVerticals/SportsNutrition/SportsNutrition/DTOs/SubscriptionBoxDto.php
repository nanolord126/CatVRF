<?php

declare(strict_types=1);

namespace App\Domains\HealthAndSports\SubVerticals\SportsNutrition\DTOs;

final readonly class SubscriptionBoxDto
{
    public function __construct(
        public string $name,
        public string $description,
        public int $priceMonthly,
        public array $includedSkus,
        public string $trainingGoal,
        public bool $isActive = true,
        public ?string $correlationId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price_monthly' => $this->priceMonthly,
            'included_skus' => $this->includedSkus,
            'training_goal' => $this->trainingGoal,
            'is_active' => $this->isActive,
            'correlation_id' => $this->correlationId,
        ];
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            name: $data['name'],
            description: $data['description'],
            priceMonthly: $data['price_monthly'],
            includedSkus: $data['included_skus'],
            trainingGoal: $data['training_goal'],
            isActive: $data['is_active'] ?? true,
            correlationId: $data['correlation_id'] ?? null,
        );
    }
}
