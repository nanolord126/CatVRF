<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Entities;

use Carbon\CarbonImmutable;

final readonly class SeniorHealthProfile
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public bool $hasHeartCondition,
        public bool $hasDiabetes,
        public bool $hasJointProblems,
        public bool $hasMobilityLimitations,
        public bool $hasBalanceIssues,
        public ?string $medications,
        public ?string allergies,
        public ?string emergencyContact,
        public ?string emergencyPhone,
        public string $fitnessLevel,
        public ?array physicalLimitations,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        bool $hasHeartCondition = false,
        bool $hasDiabetes = false,
        bool $hasJointProblems = false,
        bool $hasMobilityLimitations = false,
        bool $hasBalanceIssues = false,
        ?string $medications = null,
        ?string $allergies = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        string $fitnessLevel = 'beginner',
        ?array $physicalLimitations = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            hasHeartCondition: $hasHeartCondition,
            hasDiabetes: $hasDiabetes,
            hasJointProblems: $hasJointProblems,
            hasMobilityLimitations: $hasMobilityLimitations,
            hasBalanceIssues: $hasBalanceIssues,
            medications: $medications,
            allergies: $allergies,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            fitnessLevel: $fitnessLevel,
            physicalLimitations: $physicalLimitations,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function requiresMedicalClearance(): bool
    {
        return $this->hasHeartCondition || $this->hasDiabetes;
    }

    public function hasHighRiskFactors(): bool
    {
        $riskFactors = 0;
        if ($this->hasHeartCondition) $riskFactors++;
        if ($this->hasDiabetes) $riskFactors++;
        if ($this->hasMobilityLimitations) $riskFactors++;
        if ($this->hasBalanceIssues) $riskFactors++;

        return $riskFactors >= 2;
    }
}
