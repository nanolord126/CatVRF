<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\DTOs\Staff;

/**
 * RecordWellnessMetricsDTO — DTO для записи метрик благополучия
 * 
 * Immutable DTO following CatVRF rules
 */
final readonly class RecordWellnessMetricsDTO
{
    public function __construct(
        public int $tenantId,
        public int $employeeId,
        public ?int $stressLevel,
        public ?int $sleepHours,
        public ?int $workHours,
        public ?int $breaksTaken,
        public ?int $moodScore,
        public ?int $energyLevel,
        public ?int $workLifeBalance,
        public ?float $stepsCount,
        public ?float $activeMinutes,
        public array $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            employeeId: $data['employee_id'],
            stressLevel: $data['stress_level'] ?? null,
            sleepHours: $data['sleep_hours'] ?? null,
            workHours: $data['work_hours'] ?? null,
            breaksTaken: $data['breaks_taken'] ?? null,
            moodScore: $data['mood_score'] ?? null,
            energyLevel: $data['energy_level'] ?? null,
            workLifeBalance: $data['work_life_balance'] ?? null,
            stepsCount: $data['steps_count'] ?? null,
            activeMinutes: $data['active_minutes'] ?? null,
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'employee_id' => $this->employeeId,
            'stress_level' => $this->stressLevel,
            'sleep_hours' => $this->sleepHours,
            'work_hours' => $this->workHours,
            'breaks_taken' => $this->breaksTaken,
            'mood_score' => $this->moodScore,
            'energy_level' => $this->energyLevel,
            'work_life_balance' => $this->workLifeBalance,
            'steps_count' => $this->stepsCount,
            'active_minutes' => $this->activeMinutes,
            'metadata' => $this->metadata,
        ];
    }
}
