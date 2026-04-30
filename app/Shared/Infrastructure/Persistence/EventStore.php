<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence;

use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Domain\Events\IEventPublisher;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;

/**
 * Outbox Pattern Implementation: Event Store for guaranteed delivery
 */
final readonly class EventStore implements IEventPublisher
{
    public function __construct(
        private readonly OutboxMessage $outboxMessage,
        private readonly DatabaseManager $db,
    ) {}

    /**
     * Store domain events in outbox table within transaction
     *
     * @param  array<DomainEvent>  $events
     */
    public function publish(array $events): void
    {
        if (empty($events)) {
            return;
        }

        $this->db->transaction(function () use ($events) {
            foreach ($events as $event) {
                $this->storeEvent($event);
            }
        });
    }

    private function storeEvent(DomainEvent $event): void
    {
        $payload = $event->getPayload();
        $eventType = $event->getEventName();

        // Determine queue and priority based on event type
        [$queue, $priority] = $this->determineQueueAndPriority($eventType);

        $this->outboxMessage->create([
            'id' => Uuid::uuid4()->toString(),
            'event_type' => $eventType,
            'payload' => $this->maskPii($payload),
            'correlation_id' => $event->getCorrelationId(),
            'causation_id' => $this->extractCausationId($event),
            'user_id' => $this->extractUserId($payload),
            'tenant_id' => $this->extractTenantId($payload),
            'status' => OutboxMessage::STATUS_PENDING,
            'processing_attempts' => 0,
            'priority' => $priority,
            'queue' => $queue,
        ]);
    }

    /**
     * Determine queue and priority based on event type
     *
     * @return array{string, int}
     */
    private function determineQueueAndPriority(string $eventType): array
    {
        $eventTypeLower = strtolower($eventType);

        // Emergency events - highest priority
        if (str_contains($eventTypeLower, 'emergency') || str_contains($eventTypeLower, 'critical')) {
            return ['emergency', OutboxMessage::PRIORITY_CRITICAL];
        }

        // Payment events - high priority
        if (str_contains($eventTypeLower, 'payment') || str_contains($eventTypeLower, 'refund') || str_contains($eventTypeLower, 'fraud')) {
            return ['payment', OutboxMessage::PRIORITY_HIGH];
        }

        // Medical events - high priority
        if (str_contains($eventTypeLower, 'medical') || str_contains($eventTypeLower, 'appointment') || str_contains($eventTypeLower, 'health')) {
            return ['notification', OutboxMessage::PRIORITY_HIGH];
        }

        // Default queue
        return ['default', OutboxMessage::PRIORITY_NORMAL];
    }

    /**
     * Mask PII data before storing in outbox (compliance: 152-ФЗ, ФЗ-323)
     *
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function maskPii(array $payload): array
    {
        $piiFields = [
            'email', 'phone', 'passport', 'snils', 'inn',
            'full_name', 'first_name', 'last_name', 'middle_name',
            'address', 'birth_date', 'card_number', 'iban',
        ];

        foreach ($payload as $key => $value) {
            if (in_array($key, $piiFields, true)) {
                $payload[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $payload[$key] = $this->maskPii($value);
            }
        }

        return $payload;
    }

    private function maskValue(mixed $value): string
    {
        if (! is_string($value)) {
            return '***';
        }

        $length = strlen($value);

        if ($length <= 4) {
            return '****';
        }

        // Show first 2 and last 2 characters
        return substr($value, 0, 2).str_repeat('*', $length - 4).substr($value, -2);
    }

    private function extractCausationId(DomainEvent $event): ?string
    {
        // Try to extract causation ID from payload or metadata
        $payload = $event->getPayload();

        return $payload['causation_id'] ?? $payload['causationId'] ?? null;
    }

    private function extractUserId(array $payload): ?int
    {
        $userId = $payload['user_id'] ?? $payload['userId'] ?? $payload['user']['id'] ?? null;

        return $userId !== null ? (int) $userId : null;
    }

    private function extractTenantId(array $payload): ?int
    {
        $tenantId = $payload['tenant_id'] ?? $payload['tenantId'] ?? $payload['tenant']['id'] ?? null;

        return $tenantId !== null ? (int) $tenantId : null;
    }
}
