<?php

declare(strict_types=1);
namespace App\Shared\Domain\Events;

abstract class DomainEvent
{
    private \DateTimeImmutable $occurredAt;

    public function __construct(protected mixed $correlationId = null)
    {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function occurredOn(): string
    {
        return $this->occurredAt->format(DATE_ATOM);
    }

    public function getEventName(): string
    {
        if (method_exists($this, 'eventName')) {
            /** @phpstan-ignore-next-line */
            return (string) $this->eventName();
        }

        return static::class;
    }

    public function getPayload(): array
    {
        if (method_exists($this, 'toArray')) {
            /** @phpstan-ignore-next-line */
            return (array) $this->toArray();
        }

        return [];
    }

    public function getCorrelationId(): string
    {
        if (is_object($this->correlationId) && method_exists($this->correlationId, 'toString')) {
            /** @phpstan-ignore-next-line */
            return (string) $this->correlationId->toString();
        }

        return (string) ($this->correlationId ?? '');
    }
}
