<?php

declare(strict_types=1);

namespace Modules\Analytics\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * Behavioral Event Domain Entity
 *
 * Represents a single behavioral event tracked for analytics.
 * This is a Domain entity - should not have infrastructure dependencies.
 */
final readonly class BehavioralEvent
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly int $tenantId,
        public readonly string $eventType,
        public readonly ?string $entityType,
        public readonly ?int $entityId,
        public readonly array $metadata,
        public readonly CarbonImmutable $occurredAt,
    ) {}

    public static function create(
        int $userId,
        int $tenantId,
        string $eventType,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
    ): self {
        return new self(
            id: null,
            userId: $userId,
            tenantId: $tenantId,
            eventType: $eventType,
            entityType: $entityType,
            entityId: $entityId,
            metadata: $metadata,
            occurredAt: CarbonImmutable::now(),
        );
    }

    public function withId(int $id): self
    {
        return new self(
            id: $id,
            userId: $this->userId,
            tenantId: $this->tenantId,
            eventType: $this->eventType,
            entityType: $this->entityType,
            entityId: $this->entityId,
            metadata: $this->metadata,
            occurredAt: $this->occurredAt,
        );
    }

    /**
     * Check if this event is relevant for RFM analysis.
     */
    public function isRelevantForRFM(): bool
    {
        return in_array($this->eventType, [
            'order_completed',
            'booking_confirmed',
            'payment_successful',
        ], true);
    }

    /**
     * Get monetary value from metadata if present.
     */
    public function getMonetaryValue(): float
    {
        return (float) ($this->metadata['monetary_value'] ?? 0.0);
    }
}
