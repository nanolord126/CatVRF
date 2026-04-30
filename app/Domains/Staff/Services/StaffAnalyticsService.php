<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Domain\Entities\Staff;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Carbon\Carbon;

/**
 * StaffAnalyticsService — сервис аналитики сотрудников.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Командная динамика, оптимизация расписания, прогноз найма, KPI dashboard.
 */
final class StaffAnalyticsService
{
    use WithAuditLogging;

    public function __construct(
        private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Получает KPI по отделу.
     */
    public function getDepartmentKPI(int $tenantId, ?int $departmentId = null): array
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_analytics_kpi',
            'tenant_id' => $tenantId,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $cacheKey = "department_kpi:{$tenantId}:" . ($departmentId ?? 'all');

        return Cache::tags(['staff_analytics'])->remember(
            $cacheKey,
            now()->addHours(2),
            function () use ($tenantId, $departmentId) {
                $query = Staff::where('tenant_id', $tenantId);
                
                if ($departmentId) {
                    $query->where('department_id', $departmentId);
                }

                $staff = $query->get();

                return [
                    'total_staff' => $staff->count(),
                    'active_staff' => $staff->where('status', 'active')->count(),
                    'average_efficiency' => $staff->avg('efficiency_score') ?? 0,
                    'average_performance' => $staff->avg('performance_score') ?? 0,
                    'total_shifts_this_month' => $this->getTotalShiftsThisMonth($tenantId, $departmentId),
                    'absence_rate' => $this->getAbsenceRate($tenantId, $departmentId),
                    'turnover_rate' => $this->getTurnoverRate($tenantId, $departmentId),
                ];
            },
        );
    }

    /**
     * Анализирует командную динамику.
     */
    public function analyzeTeamDynamics(int $tenantId, array $teamIds): array
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_analytics_team_dynamics',
            'tenant_id' => $tenantId,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $cacheKey = "team_dynamics:{$tenantId}:" . md5(implode(',', $teamIds));

        return Cache::tags(['staff_analytics'])->remember(
            $cacheKey,
            now()->addHours(6),
            function () use ($teamIds) {
                $team = Staff::whereIn('id', $teamIds)->get();

                return [
                    'team_size' => $team->count(),
                    'avg_tenure_days' => $team->avg(fn ($s) => $s->hired_at ? $s->hired_at->diffInDays(now()) : 0),
                    'performance_variance' => $this->calculateVariance($team->pluck('performance_score')->toArray()),
                    'collaboration_score' => $team->avg('collaboration_score') ?? 0,
                    'skill_diversity' => $this->calculateSkillDiversity($teamIds),
                    'recommendations' => $this->generateTeamRecommendations($team),
                ];
            },
        );
    }

    /**
     * Прогнозирует потребность в найме.
     */
    public function forecastHiringNeeds(int $tenantId, int $monthsAhead = 6): array
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_analytics_hiring_forecast',
            'tenant_id' => $tenantId,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        $cacheKey = "hiring_forecast:{$tenantId}:{$monthsAhead}m";

        return Cache::tags(['staff_analytics'])->remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($tenantId, $monthsAhead) {
                $currentStaff = Staff::where('tenant_id', $tenantId)->count();
                $historicalTurnover = $this->getHistoricalTurnover($tenantId, 6);
                
                // Простой прогноз на основе исторического оттока
                $avgMonthlyTurnover = $historicalTurnover / 6;
                $projectedTurnover = $avgMonthlyTurnover * $monthsAhead;
                
                // Рост бизнеса (упрощённо - 5% в месяц)
                $growthFactor = 1.05 ** $monthsAhead;
                $projectedNeed = (int) ($currentStaff * $growthFactor - $currentStaff + $projectedTurnover);

                return [
                    'current_staff' => $currentStaff,
                    'projected_turnover' => (int) $projectedTurnover,
                    'projected_growth' => (int) ($currentStaff * $growthFactor) - $currentStaff,
                    'total_hiring_need' => max(0, $projectedNeed),
                    'by_month' => $this->generateMonthlyForecast($avgMonthlyTurnover, $currentStaff, $monthsAhead),
                ];
            },
        );
    }

    /**
     * Получает данные для dashboard.
     */
    public function getDashboardData(int $tenantId): array
    {
        return [
            'kpi' => $this->getDepartmentKPI($tenantId),
            'hiring_forecast' => $this->forecastHiringNeeds($tenantId),
            'high_performers' => $this->getTopPerformers($tenantId, 5),
            'at_risk_staff' => app(StaffAIBurnoutPredictorService::class)->getHighBurnoutRiskStaff($tenantId),
            'leaderboard' => app(StaffGamificationService::class)->getLeaderboard($tenantId, 5),
        ];
    }

    /**
     * Оптимизирует расписание на основе данных.
     */
    public function optimizeSchedule(int $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $fraudResult = $this->fraudControlService->checkRequest([
            'action' => 'staff_analytics_schedule_optimize',
            'tenant_id' => $tenantId,
        ]);

        if (!$fraudResult->isAllowed()) {
            throw new \RuntimeException('Request blocked by fraud control');
        }

        // Получаем данные о загрузке
        $shifts = StaffShift::whereBetween('shift_date', [$startDate, $endDate])
            ->whereHas('staff', fn ($q) => $q->where('tenant_id', $tenantId))
            ->get()
            ->groupBy('shift_date');

        $recommendations = [];

        foreach ($shifts as $date => $dateShifts) {
            $count = $dateShifts->count();
            
            if ($count < 5) {
                $recommendations[] = [
                    'date' => $date,
                    'issue' => 'understaffed',
                    'current' => $count,
                    'recommended' => 5,
                    'message' => 'Need additional staff for optimal coverage',
                ];
            } elseif ($count > 10) {
                $recommendations[] = [
                    'date' => $date,
                    'issue' => 'overstaffed',
                    'current' => $count,
                    'recommended' => 8,
                    'message' => 'Consider reducing staff for cost optimization',
                ];
            }
        }

        return [
            'period' => $startDate->toDateString() . ' - ' . $endDate->toDateString(),
            'total_shifts' => $shifts->flatten()->count(),
            'recommendations' => $recommendations,
            'optimization_score' => count($recommendations) === 0 ? 100 : max(0, 100 - (count($recommendations) * 10)),
        ];
    }

    private function getTotalShiftsThisMonth(int $tenantId, ?int $departmentId): int
    {
        $query = StaffShift::whereMonth('shift_date', now()->month)
            ->whereYear('shift_date', now()->year)
            ->whereHas('staff', fn ($q) => $q->where('tenant_id', $tenantId));

        if ($departmentId) {
            $query->whereHas('staff', fn ($q) => $q->where('department_id', $departmentId));
        }

        return $query->count();
    }

    private function getAbsenceRate(int $tenantId, ?int $departmentId): float
    {
        $totalShifts = $this->getTotalShiftsThisMonth($tenantId, $departmentId);
        
        if ($totalShifts === 0) {
            return 0;
        }

        $missedShifts = StaffShift::whereMonth('shift_date', now()->month)
            ->whereYear('shift_date', now()->year)
            ->where('status', 'missed')
            ->whereHas('staff', fn ($q) => $q->where('tenant_id', $tenantId))
            ->count();

        return ($missedShifts / $totalShifts) * 100;
    }

    private function getTurnoverRate(int $tenantId, ?int $departmentId): float
    {
        // Упрощённый расчёт - сотрудники покинувшие компанию за последние 3 месяца
        $query = Staff::where('tenant_id', $tenantId)
            ->where('status', '!=', 'active')
            ->where('deleted_at', '>=', now()->subMonths(3));

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $departed = $query->count();
        $total = Staff::where('tenant_id', $tenantId)->count();

        return $total > 0 ? ($departed / $total) * 100 : 0;
    }

    private function getHistoricalTurnover(int $tenantId, int $months): int
    {
        return Staff::where('tenant_id', $tenantId)
            ->where('status', '!=', 'active')
            ->where('deleted_at', '>=', now()->subMonths($months))
            ->count();
    }

    private function calculateVariance(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }

    private function calculateSkillDiversity(array $teamIds): array
    {
        $skills = \App\Domains\Staff\Domain\Entities\StaffSkill::whereIn('staff_id', $teamIds)
            ->get()
            ->groupBy('name');

        return [
            'total_unique_skills' => $skills->count(),
            'skills' => $skills->map(fn ($s) => [
                'name' => $s->first()->name,
                'staff_count' => $s->count(),
                'avg_proficiency' => $s->avg('proficiency_level'),
            ])->toArray(),
        ];
    }

    private function generateTeamRecommendations($team): array
    {
        $recommendations = [];

        if ($team->count() < 3) {
            $recommendations[] = 'Consider adding more team members for better collaboration';
        }

        $performanceVariance = $this->calculateVariance($team->pluck('performance_score')->toArray());
        if ($performanceVariance > 20) {
            $recommendations[] = 'High performance variance - consider mentorship for lower performers';
        }

        return $recommendations;
    }

    private function generateMonthlyForecast(float $avgTurnover, int $currentStaff, int $months): array
    {
        $forecast = [];
        $staff = $currentStaff;

        for ($i = 1; $i <= $months; $i++) {
            $staff = (int) ($staff * 1.05); // 5% growth
            $departures = (int) $avgTurnover;
            $forecast[] = [
                'month' => $i,
                'projected_staff' => $staff,
                'departures' => $departures,
                'hiring_need' => max(0, (int) ($currentStaff * 1.05 * $i) - $currentStaff + ($departures * $i)),
            ];
        }

        return $forecast;
    }

    private function getTopPerformers(int $tenantId, int $limit): array
    {
        return Staff::where('tenant_id', $tenantId)
            ->orderByDesc('efficiency_score')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->full_name ?? 'Unknown',
                'efficiency_score' => $s->efficiency_score,
                'performance_score' => $s->performance_score,
            ])
            ->toArray();
    }
}
