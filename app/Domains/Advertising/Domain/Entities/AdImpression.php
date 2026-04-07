<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Entities;

use Carbon\Carbon;

/**
 * AdImpression DDD Entity.
 *
 * Represents a single ad impression event.
 * Immutable value object with public readonly properties.
 *
 * @package App\Domains\Advertising\Domain\Entities
 */
final class AdImpression
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $campaign_id,
        public readonly int $placement_id,
        public readonly ?int $user_id,
        public readonly string $ip_address,
        public readonly string $device_fingerprint,
        public readonly int $cost,
        public readonly Carbon $created_at,
        public readonly ?string $correlation_id,
    ) {}

    /**
     * Check whether this impression was served to an authenticated user.
     */
    public function isAuthenticated(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get the cost in rubles (from cents).
     */
    public function costInRubles(): float
    {
        return $this->cost / 100;
    }

    /**
     * Check if impression belongs to given campaign.
     */
    public function belongsToCampaign(int $campaignId): bool
    {
        return $this->campaign_id === $campaignId;
    }

    /**
     * String representation for debugging.
     */
    public function __toString(): string
    {
        return static::class . '::' . ($this->id ?? 'new')
            . ':campaign=' . $this->campaign_id;
    }

    /**
     * Validate essential fields are present.
     */
    public function isValid(): bool
    {
        return $this->campaign_id > 0
            && $this->cost >= 0
            && $this->ip_address !== ''
            && $this->device_fingerprint !== '';
    }
}
