<?php

declare(strict_types=1);

namespace App\Domains\Medical\Application\Events;

use App\Shared\Application\Events\ApplicationEvent;
use App\Domains\Medical\Domain\Events\AppointmentBookedDomainEvent;
use Ramsey\Uuid\Uuid;

/**
 * Application Event: Appointment booked
 * Orchestrates cross-boundary operations (notifications, cache invalidation, etc.)
 */
final class AppointmentBookedApplicationEvent extends ApplicationEvent
{
    public string $queue = 'notification';

    public function __construct(
        string $eventId,
        string $eventType,
        array $payload,
        string $correlationId,
        ?string $causationId = null,
        ?int $userId = null,
        ?int $tenantId = null,
    ) {
        parent::__construct($eventId, $eventType, $payload, $correlationId, $causationId, $userId, $tenantId);
    }

    public static function fromDomainEvent(object $domainEvent): self
    {
        if (! $domainEvent instanceof AppointmentBookedDomainEvent) {
            throw new \InvalidArgumentException('Expected AppointmentBookedDomainEvent');
        }

        return new self(
            eventId: Uuid::uuid4()->toString(),
            eventType: $domainEvent->eventName(),
            payload: $domainEvent->toArray(),
            correlationId: $domainEvent->getCorrelationId(),
            causationId: null,
            userId: null, // Extract from context if needed
            tenantId: null, // Extract from context if needed
        );
    }
}
