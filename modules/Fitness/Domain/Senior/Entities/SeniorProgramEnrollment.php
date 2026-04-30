<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Entities;

use Carbon\CarbonImmutable;

final readonly class SeniorProgramEnrollment
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $clientId,
        public int $seniorProgramId,
        public CarbonImmutable $startDate,
        public float $progressPercent,
        public string $status,
        public ?string $medicalClearanceStatus,
        public ?CarbonImmutable $medicalClearanceDate,
        public ?array initialAssessment,
        public ?array finalAssessment,
        public ?string notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $clientId,
        int $seniorProgramId,
        CarbonImmutable $startDate,
        ?string $medicalClearanceStatus = 'pending',
        ?array $initialAssessment = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            clientId: $clientId,
            seniorProgramId: $seniorProgramId,
            startDate: $startDate,
            progressPercent: 0.0,
            status: 'pending_clearance',
            medicalClearanceStatus: $medicalClearanceStatus,
            medicalClearanceDate: null,
            initialAssessment: $initialAssessment,
            finalAssessment: null,
            notes: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function approveMedicalClearance(): self
    {
        return new self(
            ...get_object_vars($this),
            medicalClearanceStatus: 'approved',
            medicalClearanceDate: CarbonImmutable::now(),
            status: 'active',
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function updateProgress(float $percent): self
    {
        return new self(
            ...get_object_vars($this),
            progressPercent: min(100, max(0, $percent)),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function complete(): self
    {
        return new self(
            ...get_object_vars($this),
            progressPercent: 100.0,
            status: 'completed',
            updatedAt: CarbonImmutable::now(),
        );
    }
}
