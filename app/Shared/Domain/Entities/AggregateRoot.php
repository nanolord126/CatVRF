<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entities;

use App\Shared\Domain\Events\DomainEvent;

abstract class AggregateRoot extends Entity
{
    /** @var array<int, DomainEvent> */
    private array $domainEvents = [];

    public function __construct(mixed $id)
    {
    }

    protected function record(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

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
}
