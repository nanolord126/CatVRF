<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\WellnessMetricsRepositoryInterface;
use Modules\CatCRM\Domain\Staff\WellnessMetrics;
use Modules\CatCRM\Domain\Staff\ValueObjects\WellnessMetricsId;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentWellnessMetricsRepository — Layer 7: Repository Implementation
 */
final class EloquentWellnessMetricsRepository implements WellnessMetricsRepositoryInterface
{
    public function findById(WellnessMetricsId $id): ?WellnessMetrics
    {
        $record = DB::table('staff_wellness_metrics')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByEmployee(int $tenantId, int $employeeId): array
    {
        $records = DB::table('staff_wellness_metrics')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderBy('recorded_at', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findByEmployeeAndDateRange(
        int $tenantId,
        int $employeeId,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array {
        $records = DB::table('staff_wellness_metrics')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('recorded_at', '>=', $start->toDateTimeString())
            ->where('recorded_at', '<=', $end->toDateTimeString())
            ->orderBy('recorded_at', 'desc')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findLatestByEmployee(int $tenantId, int $employeeId): ?WellnessMetrics
    {
        $record = DB::table('staff_wellness_metrics')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderBy('recorded_at', 'desc')
            ->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function save(WellnessMetrics $metrics): bool
    {
        $data = [
            'tenant_id' => $metrics->tenantId,
            'employee_id' => $metrics->employeeId,
            'stress_level' => $metrics->stressLevel,
            'sleep_hours' => $metrics->sleepHours,
            'work_hours' => $metrics->workHours,
            'breaks_taken' => $metrics->breaksTaken,
            'mood_score' => $metrics->moodScore,
            'energy_level' => $metrics->energyLevel,
            'work_life_balance' => $metrics->workLifeBalance,
            'steps_count' => $metrics->stepsCount,
            'active_minutes' => $metrics->activeMinutes,
            'recorded_at' => $metrics->recordedAt->toDateTimeString(),
            'metadata' => json_encode($metrics->metadata),
            'updated_at' => $metrics->updatedAt->toDateTimeString(),
        ];

        if ($metrics->id->value === 0) {
            $data['created_at'] = $metrics->createdAt->toDateTimeString();
            $id = DB::table('staff_wellness_metrics')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_wellness_metrics')
                ->where('id', $metrics->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(WellnessMetricsId $id): bool
    {
        return DB::table('staff_wellness_metrics')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): WellnessMetrics
    {
        return new WellnessMetrics(
            id: WellnessMetricsId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            employeeId: (int) $record['employee_id'],
            stressLevel: $record['stress_level'] ? (int) $record['stress_level'] : null,
            sleepHours: $record['sleep_hours'] ? (int) $record['sleep_hours'] : null,
            workHours: $record['work_hours'] ? (int) $record['work_hours'] : null,
            breaksTaken: $record['breaks_taken'] ? (int) $record['breaks_taken'] : null,
            moodScore: $record['mood_score'] ? (int) $record['mood_score'] : null,
            energyLevel: $record['energy_level'] ? (int) $record['energy_level'] : null,
            workLifeBalance: $record['work_life_balance'] ? (int) $record['work_life_balance'] : null,
            stepsCount: $record['steps_count'] ? (float) $record['steps_count'] : null,
            activeMinutes: $record['active_minutes'] ? (float) $record['active_minutes'] : null,
            recordedAt: CarbonImmutable::parse($record['recorded_at']),
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
