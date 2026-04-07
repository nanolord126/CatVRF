<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Entities;

use App\Domains\RealEstate\Domain\Enums\ViewingStatusEnum;
use App\Domains\RealEstate\Domain\Events\ViewingCancelled;
use App\Domains\RealEstate\Domain\Events\ViewingConfirmed;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use App\Domains\RealEstate\Domain\ValueObjects\ViewingId;
use DateTimeImmutable;
use DomainException;

final class ViewingAppointment
{
    private ViewingStatusEnum $status;

    /** @var list<object> */
    private array $domainEvents = [];

    public function __construct(
        private readonly ViewingId         $id,
        private readonly PropertyId        $propertyId,
        private readonly AgentId           $agentId,
        private readonly int               $clientId,
        private readonly int               $tenantId,
        private DateTimeImmutable          $scheduledAt,
        private readonly string            $clientName,
        private readonly string            $clientPhone,
        private readonly ?string           $notes,
        private readonly string            $correlationId,
        ViewingStatusEnum                  $status = ViewingStatusEnum::Pending) {
        $this->status = $status;
    }

    /**
     * Agent confirms the viewing appointment.
     */
    public function confirm(string $correlationId): void
    {
        if ($this->status !== ViewingStatusEnum::Pending) {
            throw new DomainException(
                "Viewing {$this->id->getValue()} must be Pending to be confirmed."
            );
        }

        $this->status = ViewingStatusEnum::Confirmed;

        $this->domainEvents[] = new ViewingConfirmed(
            viewingId: $this->id->getValue(),
            propertyId: $this->propertyId->getValue(),
            clientId: $this->clientId,
            agentId: $this->agentId->getValue(),
            scheduledAt: $this->scheduledAt,
            correlationId: $correlationId,
        );
    }

    /**
     * Cancel a Pending or Confirmed viewing.
     */
    public function cancel(string $reason, string $correlationId): void
    {
        if ($this->status->isTerminal()) {
            throw new DomainException(
                "Viewing {$this->id->getValue()} is already in a terminal state and cannot be cancelled."
            );
        }

        $this->status = ViewingStatusEnum::Cancelled;

        $this->domainEvents[] = new ViewingCancelled(
            viewingId: $this->id->getValue(),
            reason: $reason,
            correlationId: $correlationId,
        );
    }

    /**
     * Mark the viewing as physically completed.
     */
    public function complete(): void
    {
        if ($this->status !== ViewingStatusEnum::Confirmed) {
            throw new DomainException(
                "Viewing {$this->id->getValue()} must be Confirmed to be completed."
            );
        }

        $this->status = ViewingStatusEnum::Completed;
    }

    public function reschedule(DateTimeImmutable $newDateTime): void
    {
        if ($this->status->isTerminal()) {
            throw new DomainException('Cannot reschedule a terminal viewing.');
        }

        $this->scheduledAt = $newDateTime;
    }

    public function getId(): ViewingId { return $this->id; }
    public function getPropertyId(): PropertyId { return $this->propertyId; }
    public function getAgentId(): AgentId { return $this->agentId; }
    public function getClientId(): int { return $this->clientId; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getScheduledAt(): DateTimeImmutable { return $this->scheduledAt; }
    public function getClientName(): string { return $this->clientName; }
    public function getClientPhone(): string { return $this->clientPhone; }
    public function getNotes(): ?string { return $this->notes; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function getStatus(): ViewingStatusEnum { return $this->status; }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
