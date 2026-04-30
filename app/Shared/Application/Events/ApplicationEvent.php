<?php

declare(strict_types=1);

namespace App\Shared\Application\Events;

use Illuminate\Contracts\Queue\ShouldQueue;

abstract class ApplicationEvent implements ShouldQueue
{
    protected readonly string $queue = 'default';

    public function __construct(
        protected readonly string $eventId,
        protected readonly string $eventType,
        protected readonly array $payload,
        protected readonly string $correlationId,
        protected readonly ?string $causationId = null,
        protected readonly ?int $userId = null,
        protected readonly ?int $tenantId = null,
    ) {}

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    abstract public static function fromDomainEvent(object $domainEvent): self;
}
