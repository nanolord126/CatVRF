<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Kids\Entities;

use Carbon\CarbonImmutable;

final readonly class KidsHealthProfile
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public string $ageGroup,
        public ?CarbonImmutable $birthDate,
        public ?string $parentName,
        public ?string $parentPhone,
        public ?string $parentEmail,
        public bool $hasAllergies,
        public ?string allergies,
        public bool $hasAsthma,
        public bool $hasHeartCondition,
        public ?string medications,
        public ?string emergencyContact,
        public ?string emergencyPhone,
        public ?array physicalLimitations,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        string $ageGroup,
        ?CarbonImmutable $birthDate = null,
        ?string $parentName = null,
        ?string $parentPhone = null,
        ?string $parentEmail = null,
        bool $hasAllergies = false,
        ?string $allergies = null,
        bool $hasAsthma = false,
        bool $hasHeartCondition = false,
        ?string $medications = null,
        ?string $emergencyContact = null,
        ?string $emergencyPhone = null,
        ?array $physicalLimitations = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            ageGroup: $ageGroup,
            birthDate: $birthDate,
            parentName: $parentName,
            parentPhone: $parentPhone,
            parentEmail: $parentEmail,
            hasAllergies: $hasAllergies,
            allergies: $allergies,
            hasAsthma: $hasAsthma,
            hasHeartCondition: $hasHeartCondition,
            medications: $medications,
            emergencyContact: $emergencyContact,
            emergencyPhone: $emergencyPhone,
            physicalLimitations: $physicalLimitations,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function requiresMedicalClearance(): bool
    {
        return $this->hasAsthma || $this->hasHeartCondition;
    }

    public function isHighRisk(): bool
    {
        return $this->hasAsthma || $this->hasHeartCondition || $this->hasAllergies;
    }
}
