<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entities;

use App\Shared\Domain\Events\DomainEvent;

abstract class AggregateRoot extends Entity
{
    /** @var array<int, DomainEvent> */
    private readonly array $domainEvents = [];

    public function __construct(mixed $id) {}

    /** @return array<int, DomainEvent> */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    /** @return array<int, DomainEvent> */
    public function pullDomainEvents(): array
    {
        return $this->releaseEvents();
    }

    protected function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
