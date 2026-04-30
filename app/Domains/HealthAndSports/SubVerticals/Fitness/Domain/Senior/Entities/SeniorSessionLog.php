<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Senior\Entities;

use Carbon\CarbonImmutable;

final readonly class SeniorSessionLog
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $enrollmentId,
        public CarbonImmutable $sessionDate,
        public ?string exerciseType,
        public ?int durationMinutes,
        public ?int heartRateBefore,
        public ?int heartRateAfter,
        public ?int bloodPressureSystolic,
        public ?int bloodPressureDiastolic,
        public ?int perceivedExertion,
        public ?string notes,
        public ?array vitals,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $enrollmentId,
        CarbonImmutable $sessionDate,
        ?string $exerciseType = null,
        ?int $durationMinutes = null,
        ?int $heartRateBefore = null,
        ?int $heartRateAfter = null,
        ?int $bloodPressureSystolic = null,
        ?int $bloodPressureDiastolic = null,
        ?int $perceivedExertion = null,
        ?string $notes = null,
        ?array $vitals = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            enrollmentId: $enrollmentId,
            sessionDate: $sessionDate,
            exerciseType: $exerciseType,
            durationMinutes: $durationMinutes,
            heartRateBefore: $heartRateBefore,
            heartRateAfter: $heartRateAfter,
            bloodPressureSystolic: $bloodPressureSystolic,
            bloodPressureDiastolic: $bloodPressureDiastolic,
            perceivedExertion: $perceivedExertion,
            notes: $notes,
            vitals: $vitals,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function hasAbnormalVitals(): bool
    {
        if ($this->heartRateAfter !== null && $this->heartRateAfter > 150) {
            return true;
        }

        if ($this->bloodPressureSystolic !== null && $this->bloodPressureSystolic > 160) {
            return true;
        }

        if ($this->bloodPressureDiastolic !== null && $this->bloodPressureDiastolic > 100) {
            return true;
        }

        return false;
    }
}
