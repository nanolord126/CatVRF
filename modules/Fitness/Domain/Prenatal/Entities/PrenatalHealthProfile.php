<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Prenatal\Entities;

use Carbon\CarbonImmutable;

final readonly class PrenatalHealthProfile
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public CarbonImmutable $dueDate,
        public string $trimester,
        public bool $hasHighRiskPregnancy,
        public bool $hasPreeclampsiaRisk,
        public bool $hasGestationalDiabetes,
        public ?string $obstetricianNotes,
        public ?string medications,
        public ?string allergies,
        public ?string emergencyContact,
        public ?string emergencyPhone,
        public ?array physicalLimitations,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        CarbonImmutable $dueDate,
        string $trimester = 'first',
        bool $hasHighRiskPregnancy = false,
        bool $hasPreeclampsiaRisk = false,
        bool $hasGestationalDiabetes = false,
        ?string $obstetricianNotes = null,
        ?string $medications = null,
        ?string $allergies = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        ?array $physicalLimitations = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            dueDate: $dueDate,
            trimester: $trimester,
            hasHighRiskPregnancy: $hasHighRiskPregnancy,
            hasPreeclampsiaRisk: $hasPreeclampsiaRisk,
            hasGestationalDiabetes: $hasGestationalDiabetes,
            obstetricianNotes: $obstetricianNotes,
            medications: $medications,
            allergies: $allergies,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            physicalLimitations: $physicalLimitations,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function requiresMedicalClearance(): bool
    {
        return $this->hasHighRiskPregnancy || $this->hasPreeclampsiaRisk;
    }

    public function isHighRisk(): bool
    {
        return $this->hasHighRiskPregnancy || $this->hasPreeclampsiaRisk || $this->hasGestationalDiabetes;
    }
}
