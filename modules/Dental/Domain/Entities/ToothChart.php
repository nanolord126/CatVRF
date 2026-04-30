<?php

declare(strict_types=1);

namespace Modules\Dental\Domain\Entities;

use Modules\Dental\Domain\Enums\NumberingSystem;
use Modules\Dental\Domain\ValueObjects\ToothChartId;
use Modules\Dental\Domain\ValueObjects\ToothStatusCollection;
use Modules\Dental\Domain\ValueObjects\PatientId;
use Modules\Dental\Domain\ValueObjects\DoctorId;
use Modules\Dental\Domain\ValueObjects\TenantId;

final readonly class ToothChart
{
    public function __construct(
        public ToothChartId $id,
        public PatientId $patientId,
        public ?DoctorId $doctorId,
        public TenantId $tenantId,
        public NumberingSystem $numberingSystem,
        public bool $isPrimary,
        public ToothStatusCollection $teeth,
        public ?array $metadata,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
        public ?\DateTimeImmutable $deletedAt,
    ) {}

    public static function create(
        PatientId $patientId,
        DoctorId|null $doctorId,
        TenantId $tenantId,
        NumberingSystem $numberingSystem,
        bool $isPrimary = true,
    ): self {
        return new self(
            id: ToothChartId::generate(),
            patientId: $patientId,
            doctorId: $doctorId,
            tenantId: $tenantId,
            numberingSystem: $numberingSystem,
            isPrimary: $isPrimary,
            teeth: new ToothStatusCollection([]),
            metadata: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
            deletedAt: null,
        );
    }

    public function withTeeth(ToothStatusCollection $teeth): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $this->doctorId,
            tenantId: $this->tenantId,
            numberingSystem: $this->numberingSystem,
            isPrimary: $this->isPrimary,
            teeth: $teeth,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function withDoctor(DoctorId $doctorId): self
    {
        return new self(
            id: $this->id,
            patientId: $this->patientId,
            doctorId: $doctorId,
            tenantId: $this->tenantId,
            numberingSystem: $this->numberingSystem,
            isPrimary: $this->isPrimary,
            teeth: $this->teeth,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            deletedAt: $this->deletedAt,
        );
    }

    public function getTeethRange(): array
    {
        return $this->isPrimary
            ? $this->numberingSystem->getAdultTeethRange()
            : $this->numberingSystem->getMilkTeethRange();
    }

    public function hasProblematicTeeth(): bool
    {
        foreach ($this->teeth as $tooth) {
            if ($tooth->status->requiresAttention()) {
                return true;
            }
        }
        return false;
    }
}
