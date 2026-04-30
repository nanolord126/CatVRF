<?php

declare(strict_types=1);

namespace Modules\VetGrooming\Domain\Entities;

use Carbon\CarbonImmutable;
use Modules\VetGrooming\Domain\Enums\VaccineType;
use Modules\VetGrooming\Domain\Enums\VaccinationStatus;

final readonly class PetVaccination
{
    public function __construct(
        public int $id,
        public string $uuid,
        public int $tenantId,
        public int $petId,
        public ?int $veterinarianId,
        public ?int $clinicId,
        public VaccineType $vaccineType,
        public string $vaccineName,
        public ?string $manufacturer,
        public ?string $batchNumber,
        public ?CarbonImmutable $expirationDate,
        public int $doseNumber,
        public int $totalDoses,
        public ?CarbonImmutable $plannedDate,
        public ?CarbonImmutable $actualDate,
        public ?CarbonImmutable $nextDueDate,
        public VaccinationStatus $status,
        public ?string $notes,
        public ?array $riskFactors,
        public ?array $reactionData,
        public ?string $correlationId,
        public ?array $tags,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
        public ?CarbonImmutable $deletedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $petId,
        VaccineType $vaccineType,
        string $vaccineName,
        ?int $veterinarianId = null,
        ?int $clinicId = null,
        ?string $manufacturer = null,
        ?string $batchNumber = null,
        ?CarbonImmutable $expirationDate = null,
        int $doseNumber = 1,
        int $totalDoses = 1,
        ?CarbonImmutable $plannedDate = null,
        ?CarbonImmutable $actualDate = null,
        ?CarbonImmutable $nextDueDate = null,
        ?string $notes = null,
        ?array $riskFactors = null,
        ?string $correlationId = null,
        ?array $tags = null,
    ): self {
        return new self(
            id: 0,
            uuid: (string) str()->uuid(),
            tenantId: $tenantId,
            petId: $petId,
            veterinarianId: $veterinarianId,
            clinicId: $clinicId,
            vaccineType: $vaccineType,
            vaccineName: $vaccineName,
            manufacturer: $manufacturer,
            batchNumber: $batchNumber,
            expirationDate: $expirationDate,
            doseNumber: $doseNumber,
            totalDoses: $totalDoses,
            plannedDate: $plannedDate,
            actualDate: $actualDate,
            nextDueDate: $nextDueDate,
            status: VaccinationStatus::PLANNED,
            notes: $notes,
            riskFactors: $riskFactors,
            reactionData: null,
            correlationId: $correlationId,
            tags: $tags,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
            deletedAt: null,
        );
    }

    public function markAsCompleted(CarbonImmutable $actualDate, ?CarbonImmutable $nextDueDate = null, ?string $notes = null): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            petId: $this->petId,
            veterinarianId: $this->veterinarianId,
            clinicId: $this->clinicId,
            vaccineType: $this->vaccineType,
            vaccineName: $this->vaccineName,
            manufacturer: $this->manufacturer,
            batchNumber: $this->batchNumber,
            expirationDate: $this->expirationDate,
            doseNumber: $this->doseNumber,
            totalDoses: $this->totalDoses,
            plannedDate: $this->plannedDate,
            actualDate: $actualDate,
            nextDueDate: $nextDueDate,
            status: VaccinationStatus::COMPLETED,
            notes: $notes ?? $this->notes,
            riskFactors: $this->riskFactors,
            reactionData: $this->reactionData,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function markAsOverdue(): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            petId: $this->petId,
            veterinarianId: $this->veterinarianId,
            clinicId: $this->clinicId,
            vaccineType: $this->vaccineType,
            vaccineName: $this->vaccineName,
            manufacturer: $this->manufacturer,
            batchNumber: $this->batchNumber,
            expirationDate: $this->expirationDate,
            doseNumber: $this->doseNumber,
            totalDoses: $this->totalDoses,
            plannedDate: $this->plannedDate,
            actualDate: $this->actualDate,
            nextDueDate: $this->nextDueDate,
            status: VaccinationStatus::OVERDUE,
            notes: $this->notes,
            riskFactors: $this->riskFactors,
            reactionData: $this->reactionData,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function recordReaction(array $reactionData): self
    {
        return new self(
            id: $this->id,
            uuid: $this->uuid,
            tenantId: $this->tenantId,
            petId: $this->petId,
            veterinarianId: $this->veterinarianId,
            clinicId: $this->clinicId,
            vaccineType: $this->vaccineType,
            vaccineName: $this->vaccineName,
            manufacturer: $this->manufacturer,
            batchNumber: $this->batchNumber,
            expirationDate: $this->expirationDate,
            doseNumber: $this->doseNumber,
            totalDoses: $this->totalDoses,
            plannedDate: $this->plannedDate,
            actualDate: $this->actualDate,
            nextDueDate: $this->nextDueDate,
            status: $this->status,
            notes: $this->notes,
            riskFactors: $this->riskFactors,
            reactionData: $reactionData,
            correlationId: $this->correlationId,
            tags: $this->tags,
            createdAt: $this->createdAt,
            updatedAt: CarbonImmutable::now(),
            deletedAt: $this->deletedAt,
        );
    }

    public function isOverdue(): bool
    {
        return $this->status === VaccinationStatus::OVERDUE
            || ($this->nextDueDate !== null && $this->nextDueDate->isPast() && $this->status !== VaccinationStatus::COMPLETED);
    }

    public function isDueWithin(int $days): bool
    {
        if ($this->nextDueDate === null || $this->status === VaccinationStatus::COMPLETED) {
            return false;
        }

        return $this->nextDueDate->diffInDays(CarbonImmutable::now()) <= $days;
    }

    public function isRabies(): bool
    {
        return $this->vaccineType === VaccineType::RABIES;
    }

    public function isCoreVaccine(): bool
    {
        return in_array($this->vaccineType, [
            VaccineType::CORE_DHP,
            VaccineType::RABIES,
            VaccineType::CORE_FPV,
            VaccineType::CORE_FCV_FHV1,
        ]);
    }
}
