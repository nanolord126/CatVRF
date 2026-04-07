<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Domain\Entities;

use App\Domains\RealEstate\Domain\Enums\ContractTypeEnum;
use App\Domains\RealEstate\Domain\Events\ContractSigned;
use App\Domains\RealEstate\Domain\ValueObjects\AgentId;
use App\Domains\RealEstate\Domain\ValueObjects\ContractId;
use App\Domains\RealEstate\Domain\ValueObjects\Price;
use App\Domains\RealEstate\Domain\ValueObjects\PropertyId;
use DateTimeImmutable;
use DomainException;

final class Contract
{
    private string $status = 'pending';

    private ?DateTimeImmutable $signedAt = null;

    private ?DateTimeImmutable $terminatedAt = null;

    /** @var list<object> */
    private array $domainEvents = [];

    public function __construct(
        private readonly ContractId      $id,
        private readonly PropertyId      $propertyId,
        private readonly AgentId         $agentId,
        private readonly int             $clientId,
        private readonly int             $tenantId,
        private readonly ContractTypeEnum $type,
        private readonly Price           $price,
        private readonly string          $correlationId,
        private ?string                  $documentUrl = null,
        private ?int                     $leaseDurationMonths = null) {}

    /**
     * Sign the contract — transitions pending → signed, emits ContractSigned.
     * Commission is 14% of deal value by canon.
     */
    public function sign(string $correlationId): void
    {
        if ($this->status !== 'pending') {
            throw new DomainException(
                "Contract {$this->id->getValue()} is not in pending status."
            );
        }

        $this->status   = 'signed';
        $this->signedAt = new DateTimeImmutable();

        $commission = $this->price->percentage($this->type->commissionPercent());

        $this->domainEvents[] = new ContractSigned(
            contractId: $this->id->getValue(),
            propertyId: $this->propertyId->getValue(),
            agentId: $this->agentId->getValue(),
            clientId: $this->clientId,
            type: $this->type->value,
            amountKopecks: $this->price->getAmountKopecks(),
            commissionKopecks: $commission->getAmountKopecks(),
            correlationId: $correlationId,
        );
    }

    /**
     * Terminate an already signed contract.
     */
    public function terminate(string $reason): void
    {
        if ($this->status !== 'signed') {
            throw new DomainException(
                "Only signed contracts can be terminated. Current status: {$this->status}."
            );
        }

        $this->status        = 'terminated';
        $this->terminatedAt  = new DateTimeImmutable();
    }

    public function attachDocument(string $url): void
    {
        $this->documentUrl = $url;
    }

    public function calculateCommission(): Price
    {
        return $this->price->percentage($this->type->commissionPercent());
    }

    public function getId(): ContractId { return $this->id; }
    public function getPropertyId(): PropertyId { return $this->propertyId; }
    public function getAgentId(): AgentId { return $this->agentId; }
    public function getClientId(): int { return $this->clientId; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getType(): ContractTypeEnum { return $this->type; }
    public function getPrice(): Price { return $this->price; }
    public function getStatus(): string { return $this->status; }
    public function getSignedAt(): ?DateTimeImmutable { return $this->signedAt; }
    public function getTerminatedAt(): ?DateTimeImmutable { return $this->terminatedAt; }
    public function getDocumentUrl(): ?string { return $this->documentUrl; }
    public function getLeaseDurationMonths(): ?int { return $this->leaseDurationMonths; }
    public function getCorrelationId(): string { return $this->correlationId; }
    public function isSigned(): bool { return $this->status === 'signed'; }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events             = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
