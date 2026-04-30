<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Corporate\Entities;

use Carbon\CarbonImmutable;

final readonly class CorporateReport
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public int $corporateEnrollmentId,
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public float $attendanceRate,
        public int $activeEmployees,
        public int $totalWorkouts,
        public ?array $topWorkouts,
        public ?array $employeeStats,
        public ?string notes,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $corporateEnrollmentId,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        float $attendanceRate,
        int $activeEmployees,
        int $totalWorkouts,
        ?array $topWorkouts = null,
        ?array $employeeStats = null,
        ?string $notes = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            corporateEnrollmentId: $corporateEnrollmentId,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            attendanceRate: $attendanceRate,
            activeEmployees: $activeEmployees,
            totalWorkouts: $totalWorkouts,
            topWorkouts: $topWorkouts,
            employeeStats: $employeeStats,
            notes: $notes,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function getEngagementScore(): float
    {
        // Simple engagement score calculation
        return ($this->attendanceRate * 0.7) + (min($this->activeEmployees / 10, 1) * 30);
    }
}
