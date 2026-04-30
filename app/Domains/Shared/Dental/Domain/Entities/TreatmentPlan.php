<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Entities;

use Modules\Dental\Domain\Enums\TreatmentPlanStatus;
use Modules\Dental\Domain\ValueObjects\TreatmentPlanId;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\TenantId;
use Modules\Dental\Domain\ValueObjects\TreatmentStepCollection;
use Modules\Dental\Domain\ValueObjects\Money;

final readonly class TreatmentPlan
{
    public function __construct(
        public TreatmentPlanId $id,
        public PatientId $patientId,
        public DoctorId $doctorId,
        public ?ToothChartId $toothChartId,
        public TenantId $tenantId,
        public string $name,
        public ?string $description,
        public TreatmentPlanStatus $status,
        public Money $totalCost,
        public Money $discountAmount,
        public Money $finalCost,
        public ?\DateTimeImmutable $startDate,
        public ?\DateTimeImmutable $estimatedCompletionDate,
        public ?\DateTimeImmutable $actualCompletionDate,
        public TreatmentStepCollection $steps,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {}

    public static function create(
        PatientId $patientId,
        DoctorId $doctorId,
        TenantId $tenantId,
        string $name,
        ?string $description = null,
        ?ToothChartId $toothChartId = null,
    ): self {
        return new self(
            id: TreatmentPlanId::generate(),
            patientId: $patientId,
            doctorId: $doctorId,
            toothChartId: $toothChartId,
            tenantId: $tenantId,
            name: $name,
            description: $description,
            status: TreatmentPlanStatus::DRAFT,
            totalCost: Money::zero(),
            discountAmount: Money::zero(),
            finalCost: Money::zero(),
            startDate: null,
            estimatedCompletionDate: null,
            actualCompletionDate: null,
            steps: new TreatmentStepCollection([]),
            metadata: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
            deletedAt: null,
        );
    }

    public function activate(): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            toothChartId: $this->toothChartId,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            status: TreatmentPlanStatus::ACTIVE,
            totalCost: $this->totalCost,
            discountAmount: $this->discountAmount,
            finalCost: $this->finalCost,
            startDate: new \DateTimeImmutable(),
            estimatedCompletionDate: $this->estimatedCompletionDate,
            actualCompletionDate: $this->actualCompletionDate,
            steps: $this->steps,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function withSteps(TreatmentStepCollection $steps): self
    {
        $totalCost = $steps->getTotalCost();
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            toothChartId: $this->toothChartId,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            status: $this->status,
            totalCost: $totalCost,
            discountAmount: $this->discountAmount,
            finalCost: $totalCost->subtract($this->discountAmount),
            startDate: $this->startDate,
            estimatedCompletionDate: $this->estimatedCompletionDate,
            actualCompletionDate: $this->actualCompletionDate,
            steps: $steps,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function withDiscount(Money $discountAmount): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            toothChartId: $this->toothChartId,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            status: $this->status,
            totalCost: $this->totalCost,
            discountAmount: $discountAmount,
            finalCost: $this->totalCost->subtract($discountAmount),
            startDate: $this->startDate,
            estimatedCompletionDate: $this->estimatedCompletionDate,
            actualCompletionDate: $this->actualCompletionDate,
            steps: $this->steps,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function markAsCompleted(): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            toothChartId: $this->toothChartId,
            tenantId: $this->tenantId,
            name: $this->name,
            description: $this->description,
            status: TreatmentPlanStatus::COMPLETED,
            totalCost: $this->totalCost,
            discountAmount: $this->discountAmount,
            finalCost: $this->finalCost,
            startDate: $this->startDate,
            estimatedCompletionDate: $this->estimatedCompletionDate,
            actualCompletionDate: new \DateTimeImmutable(),
            steps: $this->steps,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }
}
