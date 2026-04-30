<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\DTOs;

/**
 * BonusRuleDto - Data transfer object for bonus rule configuration
 * 
 * Used for creating and updating bonus rules via API or admin interface.
 */
final readonly class BonusRuleDto
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description = null,
        public string $ruleType = 'loyalty',
        public ?string $verticalCode = null,
        public ?array $config = null,
        public ?float $minAmount = null,
        public ?float $maxAmount = null,
        public ?int $maxPerUser = null,
        public ?int $maxPerDay = null,
        public int $cooldownHours = 0,
        public float $multiplier = 1.0,
        public bool $isActive = true,
        public int $priority = 0,
        public ?string $startsAt = null,
        public ?string $endsAt = null,
        public ?string $abTestVariant = null,
        public ?float $abTestTrafficPercentage = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            description: $data['description'] ?? null,
            ruleType: $data['rule_type'] ?? 'loyalty',
            verticalCode: $data['vertical_code'] ?? null,
            config: $data['config'] ?? null,
            minAmount: $data['min_amount'] ?? null,
            maxAmount: $data['max_amount'] ?? null,
            maxPerUser: $data['max_per_user'] ?? null,
            maxPerDay: $data['max_per_day'] ?? null,
            cooldownHours: $data['cooldown_hours'] ?? 0,
            multiplier: $data['multiplier'] ?? 1.0,
            isActive: $data['is_active'] ?? true,
            priority: $data['priority'] ?? 0,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
            abTestVariant: $data['ab_test_variant'] ?? null,
            abTestTrafficPercentage: $data['ab_test_traffic_percentage'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            description: $data['description'] ?? null,
            ruleType: $data['rule_type'] ?? 'loyalty',
            verticalCode: $data['vertical_code'] ?? null,
            config: $data['config'] ?? null,
            minAmount: $data['min_amount'] ?? null,
            maxAmount: $data['max_amount'] ?? null,
            maxPerUser: $data['max_per_user'] ?? null,
            maxPerDay: $data['max_per_day'] ?? null,
            cooldownHours: $data['cooldown_hours'] ?? 0,
            multiplier: $data['multiplier'] ?? 1.0,
            isActive: $data['is_active'] ?? true,
            priority: $data['priority'] ?? 0,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
            abTestVariant: $data['ab_test_variant'] ?? null,
            abTestTrafficPercentage: $data['ab_test_traffic_percentage'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'rule_type' => $this->ruleType,
            'vertical_code' => $this->verticalCode,
            'config' => $this->config,
            'min_amount' => $this->minAmount,
            'max_amount' => $this->maxAmount,
            'max_per_user' => $this->maxPerUser,
            'max_per_day' => $this->maxPerDay,
            'cooldown_hours' => $this->cooldownHours,
            'multiplier' => $this->multiplier,
            'is_active' => $this->isActive,
            'priority' => $this->priority,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'ab_test_variant' => $this->abTestVariant,
            'ab_test_traffic_percentage' => $this->abTestTrafficPercentage,
            'metadata' => $this->metadata,
        ];
    }
}
