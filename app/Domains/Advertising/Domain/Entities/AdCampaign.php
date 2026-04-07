<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * AdCampaign DDD Entity.
 *
 * Immutable value object representing an advertising campaign.
 * Properties are public readonly for cross-layer access.
 *
 * @package App\Domains\Advertising\Domain\Entities
 */
final class AdCampaign
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $uuid,
        public readonly int $tenant_id,
        public readonly string $name,
        public readonly string $status,
        public readonly Carbon $start_at,
        public readonly Carbon $end_at,
        public readonly int $budget,
        public readonly int $spent,
        public readonly string $pricing_model,
        public readonly array $targeting_criteria,
        public readonly ?string $correlation_id = null,
    ) {}

    /**
     * Factory method for creating a new draft campaign.
     */
    public static function create(
        int $tenant_id,
        string $name,
        Carbon $start_at,
        Carbon $end_at,
        int $budget,
        string $pricing_model,
        array $targeting_criteria,
        ?string $correlation_id,
    ): self {
        return new self(
            id: null,
            uuid: Str::uuid()->toString(),
            tenant_id: $tenant_id,
            name: $name,
            status: 'draft',
            start_at: $start_at,
            end_at: $end_at,
            budget: $budget,
            spent: 0,
            pricing_model: $pricing_model,
            targeting_criteria: $targeting_criteria,
            correlation_id: $correlation_id ?? Str::uuid()->toString(),
        );
    }

    /**
     * Check if campaign is currently active and within date range.
     */
    public function isActive(): bool
    {
        return $this->status === 'active'
            && Carbon::now()->between($this->start_at, $this->end_at);
    }

    /**
     * Check if campaign has remaining budget.
     */
    public function hasBudget(): bool
    {
        return $this->spent < $this->budget;
    }

    /**
     * Get remaining budget in cents.
     */
    public function remainingBudget(): int
    {
        return max(0, $this->budget - $this->spent);
    }

    /**
     * String representation for debugging.
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new') . ':' . $this->name;
    }
}
