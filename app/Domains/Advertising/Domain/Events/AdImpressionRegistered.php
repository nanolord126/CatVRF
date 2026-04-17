<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Domain event dispatched when an ad impression is registered.
 *
 * Carries campaign ID, cost and correlation_id for traceability.
 * Listeners handle analytics side effects asynchronously.
 *
 * @package App\Domains\Advertising\Domain\Events
 */
final class AdImpressionRegistered
{

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $campaignId,
        public readonly int $cost,
        public readonly string $correlationId,
    ) {}

    /**
     * Get campaign ID.
     */
    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    /**
     * Get impression cost in cents.
     */
    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * Get correlation ID for tracing.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * String representation for debugging.
     */
    public function __toString(): string
    {
        return static::class
            . '::campaign=' . $this->campaignId
            . ':cost=' . $this->cost;
    }

    /**
     * Convert to array for logging.
     */
    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'cost' => $this->cost,
            'correlation_id' => $this->correlationId,
        ];
    }
}
