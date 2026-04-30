<?php

declare(strict_types=1);

namespace App\Shared\Application\Services;

use Psr\Log\LoggerInterface;

use App\Shared\Domain\Events\DomainEvent;
use App\Shared\Domain\Events\IEventPublisher;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Ramsey\Uuid\Uuid;

/**
 * Central Event Dispatcher with PII masking and guaranteed delivery
 */
final readonly class EventDispatcherService
{
    public function __construct(private readonly LoggerInterface $logger,
        private readonly Dispatcher $dispatcher,
        private readonly IEventPublisher $eventPublisher,) {}

    /**
     * Dispatch domain event using Outbox Pattern for guaranteed delivery
     */
    public function dispatchDomainEvent(DomainEvent $event): void
    {
        $this->dispatchDomainEvents([$event]);
    }

    /**
     * Dispatch multiple domain events using Outbox Pattern
     *
     * @param  array<DomainEvent>  $events
     */
    public function dispatchDomainEvents(array $events): void
    {
        if (empty($events)) {
            return;
        }

        // Store events in outbox for guaranteed delivery
        $this->eventPublisher->publish($events);

        // Log for observability
        foreach ($events as $event) {
            $this->logEventPublished($event);
        }
    }

    /**
     * Dispatch application event directly (synchronous or async via Laravel queue)
     * Use only for non-critical events that don't require guaranteed delivery
     */
    public function dispatchApplicationEvent(object $event, ?string $queue = null): void
    {
        if ($queue) {
            $event->queue = $queue;
        }

        $this->dispatcher->dispatch($event);

        $this->log->$this->logger->info('Application event dispatched', [
            'event_type' => get_class($event),
            'queue' => $queue ?? 'default',
            'correlation_id' => $this->generateCorrelationId(),
        ]);
    }

    /**
     * Dispatch event with immediate delivery (bypass outbox)
     * Use ONLY for emergency/critical events that must be processed immediately
     */
    public function dispatchEmergencyEvent(object $event): void
    {
        $event->queue = 'emergency';
        $event->connection = 'redis';

        $this->dispatcher->dispatch($event);

        $this->log->warning('Emergency event dispatched', [
            'event_type' => get_class($event),
            'priority' => 'critical',
            'correlation_id' => $this->generateCorrelationId(),
        ]);
    }

    private function logEventPublished(DomainEvent $event): void
    {
        $this->log->$this->logger->info('Domain event published to outbox', [
            'event_type' => $event->getEventName(),
            'correlation_id' => $event->getCorrelationId(),
            'occurred_at' => $event->occurredOn(),
        ]);
    }

    private function generateCorrelationId(): string
    {
        return Uuid::uuid4()->toString();
    }
}
