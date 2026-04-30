<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Behavioral Event Data Transfer Object
 *
 * Represents a behavioral event captured for analytics purposes.
 * This DTO is used to transfer event data between layers.
 *
 * @package Modules\Analytics\Application\DTOs
 */
final readonly class BehavioralEventDto
{
    /**
     * Create a new BehavioralEventDto instance.
     *
     * @param int $userId The ID of the user who triggered the event
     * @param int|null $tenantId The tenant ID for multi-tenancy support
     * @param string $eventType The type of event (e.g., 'page_view', 'click', 'purchase')
     * @param string|null $entityType The type of entity involved (e.g., 'product', 'service')
     * @param int|null $entityId The ID of the entity involved
     * @param array $eventData Additional event metadata and payload
     * @param string|null $correlationId Optional correlation ID for distributed tracing
     * @param float $monetaryValue Optional monetary value of the event
     */
    public function __construct(
        public int $userId,
        public ?int $tenantId,
        public string $eventType,
        public ?string $entityType,
        public ?int $entityId,
        public array $eventData,
        public ?string $correlationId = null,
        public float $monetaryValue = 0.0,
    ) {
        $this->validate();
    }

    /**
     * Create DTO from array data.
     *
     * @param array $data The data to create the DTO from
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) ($data['user_id'] ?? 0),
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            eventType: (string) ($data['event_type'] ?? ''),
            entityType: $data['entity_type'] ?? null,
            entityId: isset($data['entity_id']) ? (int) $data['entity_id'] : null,
            eventData: (array) ($data['event_data'] ?? []),
            correlationId: $data['correlation_id'] ?? null,
            monetaryValue: (float) ($data['monetary_value'] ?? 0.0),
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'event_type' => $this->eventType,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'event_data' => $this->eventData,
            'correlation_id' => $this->correlationId,
            'monetary_value' => $this->monetaryValue,
        ];
    }

    /**
     * Validate the DTO data.
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    private function validate(): void
    {
        if ($this->userId <= 0) {
            throw new \InvalidArgumentException('User ID must be a positive integer');
        }

        if (empty($this->eventType)) {
            throw new \InvalidArgumentException('Event type cannot be empty');
        }

        if ($this->monetaryValue < 0) {
            throw new \InvalidArgumentException('Monetary value cannot be negative');
        }
    }

    /**
     * Check if this event is relevant for RFM analysis.
     *
     * @return bool
     */
    public function isRelevantForRFM(): bool
    {
        return in_array($this->eventType, [
            'order_completed',
            'booking_confirmed',
            'payment_successful',
            'purchase',
        ], true);
    }

    /**
     * Get the monetary value of this event.
     *
     * @return float
     */
    public function getMonetaryValue(): float
    {
        return $this->monetaryValue;
    }

    /**
     * Create a DTO with a correlation ID.
     *
     * @param string $correlationId
     * @return self
     */
    public function withCorrelationId(string $correlationId): self
    {
        return new self(
            userId: $this->userId,
            tenantId: $this->tenantId,
            eventType: $this->eventType,
            entityType: $this->entityType,
            entityId: $this->entityId,
            eventData: $this->eventData,
            correlationId: $correlationId,
            monetaryValue: $this->monetaryValue,
        );
    }

    /**
     * Create a DTO with a monetary value.
     *
     * @param float $monetaryValue
     * @return self
     */
    public function withMonetaryValue(float $monetaryValue): self
    {
        return new self(
            userId: $this->userId,
            tenantId: $this->tenantId,
            eventType: $this->eventType,
            entityType: $this->entityType,
            entityId: $this->entityId,
            eventData: $this->eventData,
            correlationId: $this->correlationId,
            monetaryValue: $monetaryValue,
        );
    }

    /**
     * Check if this DTO equals another DTO.
     *
     * @param self $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->userId === $other->userId
            && $this->tenantId === $other->tenantId
            && $this->eventType === $other->eventType
            && $this->entityType === $other->entityType
            && $this->entityId === $other->entityId
            && $this->correlationId === $other->correlationId
            && $this->monetaryValue === $other->monetaryValue;
    }
}
