<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff;

use Carbon\CarbonImmutable;
use Modules\CatCRM\Domain\Staff\ValueObjects\WellnessMetricsId;

/**
 * WellnessMetrics — Метрики благополучия сотрудника
 * 
 * Readonly DDD entity для представления метрик благополучия
 */
final readonly class WellnessMetrics
{
    public function __construct(
        public WellnessMetricsId $id,
        public int $tenantId,
        public int $employeeId,
        public ?int $stressLevel, // 0-100
        public ?int $sleepHours,
        public ?int $workHours,
        public ?int $breaksTaken,
        public ?int $moodScore, // 0-10
        public ?int energyLevel, // 0-10
        public ?int $workLifeBalance, // 0-10
        public ?float $stepsCount,
        public ?float activeMinutes,
        public CarbonImmutable $recordedAt,
        public array $metadata,
        public CarbonImmutable $createdAt,
    ) {}

    public function hasHighStress(): bool
    {
        return $this->stressLevel !== null && $this->stressLevel >= 70;
    }

    public function hasPoorSleep(): bool
    {
        return $this->sleepHours !== null && $this->sleepHours < 6;
    }

    public function hasPoorWorkLifeBalance(): bool
    {
        return $this->workLifeBalance !== null && $this->workLifeBalance <= 4;
    }
}
