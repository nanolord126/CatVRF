<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Entities;

use Modules\Dental\Domain\Enums\LabTestStatus;
use Modules\Dental\Domain\ValueObjects\LabTestId;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\LabTestTypeId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\Money;
use Modules\Dental\Domain\ValueObjects\LabResultCollection;

final readonly class LabTest
{
    public function __construct(
        public LabTestId $id,
        public PatientId $patientId,
        public DoctorId $doctorId,
        public LabTestTypeId $labTestTypeId,
        public TenantId $tenantId,
        public string $barcode,
        public LabTestStatus $status,
        public ?\DateTimeImmutable $sampleCollectedAt,
        public ?\DateTimeImmutable $completedAt,
        public Money $cost,
        public ?string $notes,
        public LabResultCollection $results,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {}

    public static function create(
        PatientId $patientId,
        DoctorId $doctorId,
        LabTestTypeId $labTestTypeId,
        TenantId $tenantId,
        string $barcode,
        Money $cost,
        ?string $notes = null,
    ): self {
        return new self(
            id: LabTestId::generate(),
            patientId: $patientId,
            doctorId: $doctorId,
            labTestTypeId: $labTestTypeId,
            tenantId: $tenantId,
            barcode: $barcode,
            status: LabTestStatus::ORDERED,
            sampleCollectedAt: null,
            completedAt: null,
            cost: $cost,
            notes: $notes,
            results: new LabResultCollection([]),
            metadata: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
            deletedAt: null,
        );
    }

    public function collectSample(): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            labTestTypeId: $this->labTestTypeId,
            tenantId: $this->tenantId,
            barcode: $this->barcode,
            status: LabTestStatus::SAMPLE_COLLECTED,
            sampleCollectedAt: new \DateTimeImmutable(),
            completedAt: $this->completedAt,
            cost: $this->cost,
            notes: $this->notes,
            results: $this->results,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function startProcessing(): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            labTestTypeId: $this->labTestTypeId,
            tenantId: $this->tenantId,
            barcode: $this->barcode,
            status: LabTestStatus::IN_PROGRESS,
            sampleCollectedAt: $this->sampleCollectedAt,
            completedAt: $this->completedAt,
            cost: $this->cost,
            notes: $this->notes,
            results: $this->results,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function complete(LabResultCollection $results): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            labTestTypeId: $this->labTestTypeId,
            tenantId: $this->tenantId,
            barcode: $this->barcode,
            status: LabTestStatus::COMPLETED,
            sampleCollectedAt: $this->sampleCollectedAt,
            completedAt: new \DateTimeImmutable(),
            cost: $this->cost,
            notes: $this->notes,
            results: $results,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function withResults(LabResultCollection $results): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            labTestTypeId: $this->labTestTypeId,
            tenantId: $this->tenantId,
            barcode: $this->barcode,
            status: $this->status,
            sampleCollectedAt: $this->sampleCollectedAt,
            completedAt: $this->completedAt,
            cost: $this->cost,
            notes: $this->notes,
            results: $results,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }
}
