<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Domains\Staff\Domain\Entities\StaffWellnessMetric;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffWellnessService — сервис благополучия сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Управляет work-life balance, напоминаниями о перерывах,
 * оценкой стресса, интеграцией с фитнес-трекерами.
 */
final class StaffWellnessService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Записывает метрику благополучия.
     */
    public function recordWellnessMetric(array $data): StaffWellnessMetric
    {
        $fraudResult = $this->fraud->checkRequest([
            'action' => 'staff_wellness_record',
            'staff_id' => $data['staff_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $metric = StaffWellnessMetric::create([
            'tenant_id' => $data['tenant_id'],
            'staff_id' => $data['staff_id'],
            'metric_date' => $data['metric_date'] ?? now(),
            'stress_level' => $data['stress_level'] ?? null,
            'energy_level' => $data['energy_level'] ?? null,
            'sleep_hours' => $data['sleep_hours'] ?? null,
            'work_hours' => $data['work_hours'] ?? null,
            'break_minutes' => $data['break_minutes'] ?? null,
            'mood_score' => $data['mood_score'] ?? null,
            'physical_activity_minutes' => $data['physical_activity_minutes'] ?? null,
            'water_intake_ml' => $data['water_intake_ml'] ?? null,
            'stand_hours' => $data['stand_hours'] ?? null,
            'wellness_score' => $this->calculateWellnessScore($data),
            'notes' => $data['notes'] ?? null,
        ]);

        Cache::tags(['staff_wellness', 'staff:' . $data['staff_id']])->flush();

        $this->logCreated('staff_wellness_metric', $metric->id, [
            'staff_id' => $data['staff_id'],
            'wellness_score' => $metric->wellness_score,
        ]);

        return $metric;
    }

    /**
     * Получает тренд благополучия сотрудника.
     */
    public function getWellnessTrend(int $staffId, int $days = 30): array
    {
        $cacheKey = "wellness_trend:{$staffId}:{$days}d";

        return Cache::tags(['staff_wellness', 'staff:' . $staffId])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($staffId, $days) {
                return StaffWellnessMetric::where('staff_id', $staffId)
                    ->where('metric_date', '>=', now()->subDays($days))
                    ->orderBy('metric_date')
                    ->get()
                    ->map(fn ($m) => [
                        'date' => $m->metric_date->toDateString(),
                        'wellness_score' => $m->wellness_score,
                        'stress_level' => $m->stress_level,
                        'energy_level' => $m->energy_level,
                        'sleep_hours' => $m->sleep_hours,
                        'work_hours' => $m->work_hours,
                    ])
                    ->toArray();
            }
        );
    }

    /**
     * Анализирует work-life balance.
     */
    public function analyzeWorkLifeBalance(int $staffId): array
    {
        $metrics = StaffWellnessMetric::where('staff_id', $staffId)
            ->where('metric_date', '>=', now()->subDays(30))
            ->get();

        if ($metrics->isEmpty()) {
            return [
                'status' => 'insufficient_data',
                'message' => 'Not enough data to analyze',
            ];
        }

        $avgWorkHours = $metrics->avg('work_hours') ?? 0;
        $avgSleepHours = $metrics->avg('sleep_hours') ?? 0;
        $avgBreakMinutes = $metrics->avg('break_minutes') ?? 0;
        $avgStressLevel = $metrics->avg('stress_level') ?? 0;

        $status = 'good';
        $recommendations = [];

        if ($avgWorkHours > 10) {
            $status = 'warning';
            $recommendations[] = 'Average work hours exceed 10h/day. Consider reducing workload.';
        }

        if ($avgSleepHours < 7) {
            $status = 'warning';
            $recommendations[] = 'Average sleep is less than 7h. Prioritize rest.';
        }

        if ($avgBreakMinutes < 30) {
            $status = 'warning';
            $recommendations[] = 'Average break time is less than 30min. Take more breaks.';
        }

        if ($avgStressLevel > 7) {
            $status = 'critical';
            $recommendations[] = 'High stress levels detected. Consider stress management techniques.';
        }

        return [
            'status' => $status,
            'metrics' => [
                'avg_work_hours' => round($avgWorkHours, 2),
                'avg_sleep_hours' => round($avgSleepHours, 2),
                'avg_break_minutes' => round($avgBreakMinutes, 2),
                'avg_stress_level' => round($avgStressLevel, 2),
            ],
            'recommendations' => $recommendations,
            'wellness_score' => $metrics->last()?->wellness_score ?? 0,
        ];
    }

    /**
     * Создаёт напоминание о перерыве.
     */
    public function createBreakReminder(int $staffId, Carbon $scheduledFor): void
    {
        // Dispatch job to send reminder at scheduled time
        \App\Jobs\Staff\SendBreakReminderJob::dispatch($staffId, $scheduledFor);

        $this->logAction('break_reminder_scheduled', [
            'entity_type' => 'staff',
            'entity_id' => $staffId,
            'scheduled_for' => $scheduledFor->toIso8601String(),
        ]);
    }

    /**
     * Оценивает уровень стресса на основе метрик.
     */
    public function assessStressLevel(int $staffId): array
    {
        $recentMetrics = StaffWellnessMetric::where('staff_id', $staffId)
            ->where('metric_date', '>=', now()->subDays(7))
            ->latest()
            ->first();

        if (!$recentMetrics) {
            return [
                'stress_level' => 'unknown',
                'message' => 'No recent data available',
            ];
        }

        $stressLevel = match(true) {
            $recentMetrics->stress_level >= 8 => 'high',
            $recentMetrics->stress_level >= 5 => 'moderate',
            default => 'low',
        };

        $recommendations = match($stressLevel) {
            'high' => [
                'Take immediate action to reduce stress',
                'Consider taking a day off',
                'Practice relaxation techniques',
                'Speak with manager about workload',
            ],
            'moderate' => [
                'Monitor stress levels closely',
                'Ensure adequate rest',
                'Take regular breaks',
            ],
            'low' => [
                'Maintain current habits',
                'Continue wellness practices',
            ],
            default => [],
        };

        return [
            'stress_level' => $stressLevel,
            'score' => $recentMetrics->stress_level,
            'recommendations' => $recommendations,
            'assessed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Интегрирует данные из фитнес-трекера.
     */
    public function syncFitnessTrackerData(int $staffId, array $trackerData): void
    {
        $this->fraud->check(
            userId: $staffId,
            operationType: 'fitness_tracker_sync',
            amount: 0,
            correlationId: (string) \Illuminate\Support\Str::uuid()
        );

        $this->recordWellnessMetric([
            'tenant_id' => Staff::findOrFail($staffId)->tenant_id,
            'staff_id' => $staffId,
            'metric_date' => now(),
            'physical_activity_minutes' => $trackerData['activity_minutes'] ?? null,
            'sleep_hours' => $trackerData['sleep_hours'] ?? null,
            'steps' => $trackerData['steps'] ?? null,
            'calories_burned' => $trackerData['calories'] ?? null,
            'heart_rate_avg' => $trackerData['heart_rate_avg'] ?? null,
        ]);

        $this->logAction('fitness_tracker_synced', [
            'entity_type' => 'staff',
            'entity_id' => $staffId,
            'tracker_type' => $trackerData['tracker_type'] ?? 'unknown',
        ]);
    }

    /**
     * Получает сотрудников с высоким уровнем стресса.
     */
    public function getHighStressStaff(int $tenantId): array
    {
        $cacheKey = "high_stress_staff:{$tenantId}";

        return Cache::tags(['staff_wellness'])->remember(
            $cacheKey,
            now()->addHours(12),
            function () use ($tenantId) {
                return StaffWellnessMetric::where('tenant_id', $tenantId)
                    ->where('metric_date', '>=', now()->subDays(7))
                    ->where('stress_level', '>=', 7)
                    ->with('staff')
                    ->get()
                    ->groupBy('staff_id')
                    ->map(fn ($group) => [
                        'staff_id' => $group->first()->staff_id,
                        'staff_name' => $group->first()->staff->full_name ?? 'Unknown',
                        'avg_stress_level' => $group->avg('stress_level'),
                        'latest_metric_date' => $group->first()->metric_date->toDateString(),
                    ])
                    ->toArray();
            }
        );
    }

    /**
     * Рассчитывает общий score благополучия.
     */
    private function calculateWellnessScore(array $data): float
    {
        $score = 100;

        // Deduct points for high stress
        if (isset($data['stress_level']) && $data['stress_level'] > 5) {
            $score -= ($data['stress_level'] - 5) * 5;
        }

        // Deduct points for low sleep
        if (isset($data['sleep_hours']) && $data['sleep_hours'] < 7) {
            $score -= (7 - $data['sleep_hours']) * 5;
        }

        // Deduct points for excessive work hours
        if (isset($data['work_hours']) && $data['work_hours'] > 10) {
            $score -= ($data['work_hours'] - 10) * 3;
        }

        // Add points for physical activity
        if (isset($data['physical_activity_minutes']) && $data['physical_activity_minutes'] >= 30) {
            $score += 5;
        }

        return max(0, min(100, $score));
    }
}
