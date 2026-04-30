<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\DTOs;

use InvalidArgumentException;
use Modules\BigData\Domain\Enums\EventType;

/**
 * Event Schema Validator
 *
 * Validates event data against JSON Schema rules.
 * Ensures data quality before sending to Kafka/ClickHouse.
 */
final readonly class EventValidator
{
    private const MAX_PROPERTIES_SIZE = 10240; // 10KB
    private const MAX_CONTEXT_SIZE = 5120; // 5KB
    private const MAX_STRING_LENGTH = 1000;

    /**
     * Validate event DTO
     *
     * @throws InvalidArgumentException
     */
    public function validate(BaseEventDTO $event): void
    {
        $this->validateEventType($event->eventType);
        $this->validateTenantId($event->tenantId);
        $this->validateProperties($event->properties);
        $this->validateContext($event->context);
        $this->validateMonetaryValue($event->monetaryValue);
        $this->validateTimestamp($event->timestamp);
        $this->validateCorrelationId($event->correlationId);
        $this->validateEventSpecificRules($event);
    }

    /**
     * Validate event type
     */
    private function validateEventType(EventType $eventType): void
    {
        // EventType enum already validates this
    }

    /**
     * Validate tenant ID
     */
    private function validateTenantId(int $tenantId): void
    {
        if ($tenantId <= 0) {
            throw new InvalidArgumentException('Tenant ID must be positive');
        }
    }

    /**
     * Validate properties array
     */
    private function validateProperties(array $properties): void
    {
        $size = strlen(json_encode($properties));
        if ($size > self::MAX_PROPERTIES_SIZE) {
            throw new InvalidArgumentException(
                sprintf('Properties size exceeds maximum of %d bytes', self::MAX_PROPERTIES_SIZE)
            );
        }

        foreach ($properties as $key => $value) {
            $this->validatePropertyKey($key);
            $this->validatePropertyValue($value);
        }
    }

    /**
     * Validate property key
     */
    private function validatePropertyKey(string $key): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $key)) {
            throw new InvalidArgumentException(
                sprintf('Invalid property key: %s. Must be snake_case', $key)
            );
        }

        if (strlen($key) > 100) {
            throw new InvalidArgumentException('Property key too long (max 100 chars)');
        }
    }

    /**
     * Validate property value
     */
    private function validatePropertyValue(mixed $value): void
    {
        if (is_string($value) && strlen($value) > self::MAX_STRING_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('String value too long (max %d chars)', self::MAX_STRING_LENGTH)
            );
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $this->validatePropertyValue($item);
            }
        }
    }

    /**
     * Validate context array
     */
    private function validateContext(array $context): void
    {
        $size = strlen(json_encode($context));
        if ($size > self::MAX_CONTEXT_SIZE) {
            throw new InvalidArgumentException(
                sprintf('Context size exceeds maximum of %d bytes', self::MAX_CONTEXT_SIZE)
            );
        }
    }

    /**
     * Validate monetary value
     */
    private function validateMonetaryValue(?float $monetaryValue): void
    {
        if ($monetaryValue !== null) {
            if ($monetaryValue < 0) {
                throw new InvalidArgumentException('Monetary value cannot be negative');
            }

            if ($monetaryValue > 999999999.99) {
                throw new InvalidArgumentException('Monetary value exceeds maximum');
            }
        }
    }

    /**
     * Validate timestamp
     */
    private function validateTimestamp(\Carbon\CarbonImmutable $timestamp): void
    {
        $now = \Carbon\CarbonImmutable::now();
        $maxFuture = $now->addHours(1);
        $maxPast = $now->subDays(30);

        if ($timestamp->greaterThan($maxFuture)) {
            throw new InvalidArgumentException('Timestamp cannot be more than 1 hour in the future');
        }

        if ($timestamp->lessThan($maxPast)) {
            throw new InvalidArgumentException('Timestamp cannot be more than 30 days in the past');
        }
    }

    /**
     * Validate correlation ID
     */
    private function validateCorrelationId(string $correlationId): void
    {
        if (empty($correlationId)) {
            throw new InvalidArgumentException('Correlation ID cannot be empty');
        }

        if (strlen($correlationId) > 100) {
            throw new InvalidArgumentException('Correlation ID too long (max 100 chars)');
        }
    }

    /**
     * Validate event-specific rules
     */
    private function validateEventSpecificRules(BaseEventDTO $event): void
    {
        match ($event->eventType) {
            EventType::OrderPlaced, EventType::OrderPaid => $this->validateOrderEvent($event),
            EventType::ProductViewed, EventType::ProductAddedToCart => $this->validateProductEvent($event),
            EventType::ReviewCreated => $this->validateReviewEvent($event),
            EventType::PaymentCompleted, EventType::PaymentFailed => $this->validatePaymentEvent($event),
            default => null,
        };
    }

    /**
     * Validate order events
     */
    private function validateOrderEvent(BaseEventDTO $event): void
    {
        if ($event->orderId === null) {
            throw new InvalidArgumentException('Order ID is required for order events');
        }

        if ($event->monetaryValue === null) {
            throw new InvalidArgumentException('Monetary value is required for order events');
        }
    }

    /**
     * Validate product events
     */
    private function validateProductEvent(BaseEventDTO $event): void
    {
        if ($event->productId === null) {
            throw new InvalidArgumentException('Product ID is required for product events');
        }
    }

    /**
     * Validate review events
     */
    private function validateReviewEvent(BaseEventDTO $event): void
    {
        if (!isset($event->properties['rating'])) {
            throw new InvalidArgumentException('Rating is required for review events');
        }

        $rating = (int) $event->properties['rating'];
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5');
        }
    }

    /**
     * Validate payment events
     */
    private function validatePaymentEvent(BaseEventDTO $event): void
    {
        if ($event->orderId === null) {
            throw new InvalidArgumentException('Order ID is required for payment events');
        }

        if (!isset($event->properties['payment_method'])) {
            throw new InvalidArgumentException('Payment method is required for payment events');
        }
    }

    /**
     * Validate JSON schema
     */
    public function validateJsonSchema(string $json): bool
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['event_type']) || !isset($data['tenant_id']) || !isset($data['timestamp'])) {
                return false;
            }

            // Validate event_type is a valid enum
            try {
                EventType::from($data['event_type']);
            } catch (\ValueError $e) {
                return false;
            }

            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }
}
