<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Entities;

use Modules\Dental\Domain\Enums\TreatmentStepStatus;
use Modules\Dental\Domain\ValueObjects\TreatmentStepId;
use Modules\Dental\Domain\ValueObjects\TreatmentPlanId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\Money;

final readonly class TreatmentStep
{
    public function __construct(
        public TreatmentStepId $id,
        public TreatmentPlanId $treatmentPlanId,
        public string $name,
        public ?string $description,
        public ?string $toothNumber,
        public TreatmentStepStatus $status,
        public Money $cost,
        public int $sortOrder,
        public ?\DateTimeImmutable $scheduledDate,
        public ?\DateTimeImmutable $completedDate,
        public ?DoctorId $performedBy,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        TreatmentPlanId $treatmentPlanId,
        string $name,
        Money $cost,
        ?string $description = null,
        ?string $toothNumber = null,
        int $sortOrder = 0,
    ): self {
        return new self(
            id: TreatmentStepId::generate(),
            treatmentPlanId: $treatmentPlanId,
            name: $name,
            description: $description,
            toothNumber: $toothNumber,
            status: TreatmentStepStatus::PENDING,
            cost: $cost,
            sortOrder: $sortOrder,
            scheduledDate: null,
            completedDate: null,
            performedBy: null,
            metadata: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function schedule(\DateTimeImmutable $date): self
    {
        return new self(
            id: $this->id,
            treatmentPlanId: $this->treatmentPlanId,
            name: $this->name,
            description: $this->description,
            toothNumber: $this->toothNumber,
            status: TreatmentStepStatus::SCHEDULED,
            cost: $this->cost,
            sortOrder: $this->sortOrder,
            scheduledDate: $date,
            completedDate: $this->completedDate,
            performedBy: $this->performedBy,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function start(): self
    {
        return new self(
            id: $this->id,
            treatmentPlanId: $this->treatmentPlanId,
            name: $this->name,
            description: $this->description,
            toothNumber: $this->toothNumber,
            status: TreatmentStepStatus::IN_PROGRESS,
            cost: $this->cost,
            sortOrder: $this->sortOrder,
            scheduledDate: $this->scheduledDate,
            completedDate: $this->completedDate,
            performedBy: $this->performedBy,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }

    public function complete(DoctorId $performedBy): self
    {
        return new self(
            id: $this->id,
            treatmentPlanId: $this->treatmentPlanId,
            name: $this->name,
            description: $this->description,
            toothNumber: $this->toothNumber,
            status: TreatmentStepStatus::COMPLETED,
            cost: $this->cost,
            sortOrder: $this->sortOrder,
            scheduledDate: $this->scheduledDate,
            completedDate: new \DateTimeImmutable(),
            performedBy: $performedBy,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
        );
    }
}
