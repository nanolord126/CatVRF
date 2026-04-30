<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Domain\Events;

/**
 * Domain event dispatched when a new ad campaign is created.
 *
 * Carries campaign ID and correlation_id for full traceability.
 * Listeners handle side effects asynchronously (audit, notifications).
 */
final class AdCampaignCreated
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly int $campaignId,
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
        return self::class
            .'::campaign='.$this->campaignId
            .':cid='.$this->correlationId;
    }

    /**
     * Convert to array for logging.
     */
    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'correlation_id' => $this->correlationId,
        ];
    }
}
