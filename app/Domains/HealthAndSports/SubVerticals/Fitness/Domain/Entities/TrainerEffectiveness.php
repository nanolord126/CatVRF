<?php

declare(strict_types=1);

namespace Modules\Fitness\Domain\Entities;

use Carbon\CarbonImmutable;

final readonly class TrainerEffectiveness
{
    public function __construct(
        public int $id,
        public int $tenantId,
        public ?int $businessGroupId,
        public int $trainerId,
        public string $uuid,
        public ?string $correlationId,
        // Client metrics (60% weight)
        public ?float $retentionRate,
        public ?float $npsScore,
        public ?float $avgCheckPerClient,
        public int $repeatBookingsCount,
        public ?float $churnRate,
        // Operational metrics (25% weight)
        public ?float $occupancyRate,
        public ?float $avgGroupAttendance,
        public int $individualSessionsCount,
        public float $scheduleCompliance,
        // Qualitative metrics (15% weight)
        public ?float $managerScore,
        public bool $methodologyCompliance,
        public int $progressPhotosCount,
        // Total score
        public ?float $totalScore,
        public ?string $effectivenessLevel,
        // Period
        public CarbonImmutable $periodStart,
        public CarbonImmutable $periodEnd,
        public ?string $recommendations,
        public ?array $tags,
        public ?array $metadata,
        public CarbonImmutable $createdAt,
        public CarbonImmutable $updatedAt,
    ) {}

    public static function create(
        int $tenantId,
        int $trainerId,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        ?int $businessGroupId = null,
    ): self {
        return new self(
            id: 0,
            tenantId: $tenantId,
            businessGroupId: $businessGroupId,
            trainerId: $trainerId,
            uuid: '',
            correlationId: null,
            retentionRate: null,
            npsScore: null,
            avgCheckPerClient: null,
            repeatBookingsCount: 0,
            churnRate: null,
            occupancyRate: null,
            avgGroupAttendance: null,
            individualSessionsCount: 0,
            scheduleCompliance: 100.0,
            managerScore: null,
            methodologyCompliance: true,
            progressPhotosCount: 0,
            totalScore: null,
            effectivenessLevel: null,
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            recommendations: null,
            tags: null,
            metadata: null,
            createdAt: CarbonImmutable::now(),
            updatedAt: CarbonImmutable::now(),
        );
    }

    public function calculateTotalScore(
        float $retentionRate,
        float $npsScore,
        float $avgCheckPerClient,
        float $occupancyRate,
        float $managerScore,
    ): self {
        // Normalize scores to 0-100 range
        $normalizedRetention = min(100, $retentionRate);
        $normalizedNPS = min(100, ($npsScore + 100) / 2); // Convert -100 to 100 scale to 0-100
        $normalizedCheck = min(100, $avgCheckPerClient); // Assume max check is 100
        $normalizedOccupancy = min(100, $occupancyRate);
        $normalizedManager = $managerScore * 10; // Convert 0-10 to 0-100

        // Weighted formula: Retention 35% + NPS 25% + AvgCheck 15% + Occupancy 15% + Manager 10%
        $totalScore = ($normalizedRetention * 0.35)
            + ($normalizedNPS * 0.25)
            + ($normalizedCheck * 0.15)
            + ($normalizedOccupancy * 0.15)
            + ($normalizedManager * 0.10);

        $effectivenessLevel = $this->getEffectivenessLevel($totalScore);

        return new self(
            ...get_object_vars($this),
            totalScore: round($totalScore, 2),
            effectivenessLevel: $effectivenessLevel,
            updatedAt: CarbonImmutable::now(),
        );
    }

    private function getEffectivenessLevel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A+',
            $score >= 80 => 'A',
            $score >= 65 => 'B',
            $score >= 50 => 'C',
            default => 'D',
        };
    }

    public function isTopPerformer(): bool
    {
        return $this->effectivenessLevel === 'A+' || $this->effectivenessLevel === 'A';
    }

    public function requiresAttention(): bool
    {
        return $this->effectivenessLevel === 'C' || $this->effectivenessLevel === 'D';
    }

    public function isCritical(): bool
    {
        return $this->effectivenessLevel === 'D';
    }
}
