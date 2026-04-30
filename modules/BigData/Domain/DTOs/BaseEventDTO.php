<?php

declare(strict_types=1);

namespace Modules\BigData\Domain\DTOs;

use Carbon\CarbonImmutable;
use Modules\BigData\Domain\Enums\EventType;
use Ramsey\Uuid\Uuid;

/**
 * Base Event DTO for Big Data Tracking
 *
 * Immutable DTO representing a single analytics event.
 * All events follow this schema for consistency.
 */
final readonly class BaseEventDTO
{
    public function __construct(
        public string $eventId,
        public EventType $eventType,
        public int $tenantId,
        public ?int $userId,
        public ?int $sellerId,
        public ?int $productId,
        public ?int $orderId,
        public ?string $sessionId,
        public ?string $vertical,
        public array $properties,
        public ?float $monetaryValue,
        public array $context,
        public CarbonImmutable $timestamp,
        public string $correlationId,
        public ?string $userAgent,
        public ?string $ipAddress,
        public ?string $deviceType,
    ) {}

    /**
     * Create a new event
     */
    public static function create(
        EventType $eventType,
        int $tenantId,
        ?int $userId = null,
        ?int $sellerId = null,
        ?int $productId = null,
        ?int $orderId = null,
        ?string $sessionId = null,
        ?string $vertical = null,
        array $properties = [],
        ?float $monetaryValue = null,
        array $context = [],
        ?string $correlationId = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
        ?string $deviceType = null,
    ): self {
        return new self(
            eventId: Uuid::uuid4()->toString(),
            eventType: $eventType,
            tenantId: $tenantId,
            userId: $userId,
            sellerId: $sellerId,
            productId: $productId,
            orderId: $orderId,
            sessionId: $sessionId,
            vertical: $vertical,
            properties: $properties,
            monetaryValue: $monetaryValue,
            context: array_merge([
                'app_version' => config('app.version', '1.0.0'),
                'environment' => config('app.env'),
            ], $context),
            timestamp: CarbonImmutable::now(),
            correlationId: $correlationId ?? Uuid::uuid4()->toString(),
            userAgent: $userAgent,
            ipAddress: $ipAddress,
            deviceType: $deviceType,
        );
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_type' => $this->eventType->value,
            'event_category' => $this->eventType->getCategory(),
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'seller_id' => $this->sellerId,
            'product_id' => $this->productId,
            'order_id' => $this->orderId,
            'session_id' => $this->sessionId,
            'vertical' => $this->vertical,
            'properties' => $this->properties,
            'monetary_value' => $this->monetaryValue,
            'context' => $this->context,
            'timestamp' => $this->timestamp->toIso8601String(),
            'correlation_id' => $this->correlationId,
            'user_agent' => $this->userAgent,
            'ip_address' => $this->ipAddress,
            'device_type' => $this->deviceType,
            'pii_sensitive' => $this->eventType->isPIISensitive(),
            'enrich_clv' => $this->eventType->shouldEnrichCLV(),
        ];
    }

    /**
     * Create from array (for Kafka deserialization)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            eventId: $data['event_id'],
            eventType: EventType::from($data['event_type']),
            tenantId: (int) $data['tenant_id'],
            userId: $data['user_id'] ? (int) $data['user_id'] : null,
            sellerId: $data['seller_id'] ? (int) $data['seller_id'] : null,
            productId: $data['product_id'] ? (int) $data['product_id'] : null,
            orderId: $data['order_id'] ? (int) $data['order_id'] : null,
            sessionId: $data['session_id'] ?? null,
            vertical: $data['vertical'] ?? null,
            properties: $data['properties'] ?? [],
            monetaryValue: $data['monetary_value'] ?? null,
            context: $data['context'] ?? [],
            timestamp: CarbonImmutable::parse($data['timestamp']),
            correlationId: $data['correlation_id'],
            userAgent: $data['user_agent'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            deviceType: $data['device_type'] ?? null,
        );
    }

    /**
     * Convert to JSON string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * Create from JSON string
     */
    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * Anonymize PII data (GDPR compliance)
     */
    public function anonymize(): self
    {
        if (!$this->eventType->isPIISensitive()) {
            return $this;
        }

        return new self(
            eventId: $this->eventId,
            eventType: $this->eventType,
            tenantId: $this->tenantId,
            userId: null, // Remove user ID
            sellerId: $this->sellerId,
            productId: $this->productId,
            orderId: $this->orderId,
            sessionId: hash('sha256', $this->sessionId ?? ''),
            vertical: $this->vertical,
            properties: $this->anonymizeProperties($this->properties),
            monetaryValue: $this->monetaryValue,
            context: $this->anonymizeContext($this->context),
            timestamp: $this->timestamp,
            correlationId: $this->correlationId,
            userAgent: null,
            ipAddress: null, // Remove IP
            deviceType: $this->deviceType,
        );
    }

    private function anonymizeProperties(array $properties): array
    {
        $anonymized = $properties;
        $piiFields = ['email', 'phone', 'name', 'surname', 'firstname', 'lastname', 'address'];

        foreach ($piiFields as $field) {
            if (isset($anonymized[$field])) {
                $anonymized[$field] = hash('sha256', $anonymized[$field]);
            }
        }

        return $anonymized;
    }

    private function anonymizeContext(array $context): array
    {
        $anonymized = $context;
        unset($anonymized['user_agent']);
        unset($anonymized['ip_address']);

        return $anonymized;
    }
}
