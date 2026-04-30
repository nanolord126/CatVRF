<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ManagerKPI;
use App\Models\ManagerKPILog;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ManagerKPIService
{
    use WithAuditLogging;
    use WithTelemetry;

    public function __construct(
        private readonly ManagerBonusService $bonusService
    ) {}

    /**
     * Create KPI for manager
     */
    public function createKPI(array $data): ManagerKPI
    {
        return $this->withSpan('manager.kpi.create', function () use ($data) {
            $kpi = ManagerKPI::create([
                'manager_id' => $data['manager_id'],
                'tenant_id' => $data['tenant_id'],
                'vertical_id' => $data['vertical_id'] ?? null,
                'kpi_type' => $data['kpi_type'],
                'kpi_name' => $data['kpi_name'],
                'description' => $data['description'] ?? null,
                'target_value' => $data['target_value'],
                'target_unit' => $data['target_unit'],
                'minimum_value' => $data['minimum_value'] ?? null,
                'period_type' => $data['period_type'] ?? ManagerKPI::PERIOD_MONTHLY,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'bonus_eligible' => $data['bonus_eligible'] ?? true,
                'bonus_multiplier' => $data['bonus_multiplier'] ?? 1.0,
                'bonus_amount' => $data['bonus_amount'] ?? null,
                'targets' => $data['targets'] ?? null,
            ]);

            $this->logCreated('manager_kpi', $kpi->id, [
                'manager_id' => $kpi->manager_id,
                'kpi_type' => $kpi->kpi_type,
                'target_value' => $kpi->target_value,
            ], $data['manager_id']);

            return $kpi;
        });
    }

    /**
     * Update KPI value
     */
    public function updateKPIValue(
        int $kpiId,
        float $newValue,
        string $eventType,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ManagerKPI {
        $kpi = ManagerKPI::findOrFail($kpiId);
        $kpi->updateValue($newValue, $eventType, $referenceType, $referenceId);
        return $kpi->fresh();
    }

    /**
     * Track sale for manager KPI
     */
    public function trackSale(int $managerId, int $tenantId, float $saleAmount, ?int $verticalId = null): void
    {
        $this->withSpan('manager.kpi.track_sale', function () use (
            $managerId,
            $tenantId,
            $saleAmount,
            $verticalId
        ) {
            // Find active sales revenue KPI for current period
            $kpi = ManagerKPI::active()
                ->forManager($managerId)
                ->where('tenant_id', $tenantId)
                ->byType(ManagerKPI::TYPE_SALES_REVENUE)
                ->where('period_start', '<=', now())
                ->where('period_end', '>=', now())
                ->when($verticalId, function ($q) use ($verticalId) {
                    $q->where(function ($query) use ($verticalId) {
                        $query->where('vertical_id', $verticalId)
                            ->orWhereNull('vertical_id');
                    });
                })
                ->first();

            if ($kpi) {
                $newValue = $kpi->current_value + $saleAmount;
                $kpi->updateValue($newValue, 'sale', 'b2b_order', null);
            }

            // Track sales count KPI
            $countKpi = ManagerKPI::active()
                ->forManager($managerId)
                ->where('tenant_id', $tenantId)
                ->byType(ManagerKPI::TYPE_SALES_COUNT)
                ->where('period_start', '<=', now())
                ->where('period_end', '>=', now())
                ->when($verticalId, function ($q) use ($verticalId) {
                    $q->where(function ($query) use ($verticalId) {
                        $query->where('vertical_id', $verticalId)
                            ->orWhereNull('vertical_id');
                    });
                })
                ->first();

            if ($countKpi) {
                $newValue = $countKpi->current_value + 1;
                $countKpi->updateValue($newValue, 'sale', 'b2b_order', null);
            }
        });
    }

    /**
     * Track task completion for manager KPI
     */
    public function trackTaskCompletion(int $managerId, int $tenantId, float $kpiWeight = 1.0): void
    {
        $kpi = ManagerKPI::active()
            ->forManager($managerId)
            ->where('tenant_id', $tenantId)
            ->byType(ManagerKPI::TYPE_TASK_COMPLETION)
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();

        if ($kpi) {
            $newValue = $kpi->current_value + $kpiWeight;
            $kpi->updateValue($newValue, 'task_completed', 'crm_task', null);
        }
    }

    /**
     * Get manager KPIs for period
     */
    public function getManagerKPIs(int $managerId, ?string $periodStart = null, ?string $periodEnd = null)
    {
        $query = ManagerKPI::forManager($managerId)
            ->with('logs')
            ->orderBy('period_start', 'desc');

        if ($periodStart && $periodEnd) {
            $query->forPeriod($periodStart, $periodEnd);
        }

        return $query->paginate(20);
    }

    /**
     * Get KPI statistics for manager
     */
    public function getManagerStatistics(int $managerId, int $tenantId): array
    {
        $activeKPIs = ManagerKPI::active()
            ->forManager($managerId)
            ->where('tenant_id', $tenantId)
            ->where('period_end', '>=', now())
            ->get();

        $completedKPIs = $activeKPIs->filter(fn($kpi) => $kpi->isAchieved())->count();
        $totalKPIs = $activeKPIs->count();
        $achievementRate = $totalKPIs > 0 ? ($completedKPIs / $totalKPIs) * 100 : 0;

        $totalBonus = $activeKPIs->sum(fn($kpi) => $kpi->getBonusAmount());

        return [
            'total_active_kpis' => $totalKPIs,
            'completed_kpis' => $completedKPIs,
            'achievement_rate' => round($achievementRate, 2),
            'total_bonus_potential' => $totalBonus,
            'kpi_breakdown' => $activeKPIs->map(fn($kpi) => [
                'type' => $kpi->kpi_type,
                'name' => $kpi->kpi_name,
                'target' => $kpi->target_value,
                'current' => $kpi->current_value,
                'progress' => $kpi->progress_percentage,
                'achieved' => $kpi->isAchieved(),
                'bonus' => $kpi->getBonusAmount(),
            ])->toArray(),
        ];
    }

    /**
     * Process KPI achievement bonuses
     */
    public function processKPIAchievementBonuses(int $kpiId, int $processedBy): void
    {
        $kpi = ManagerKPI::findOrFail($kpiId);

        if (!$kpi->isAchieved() || !$kpi->bonus_eligible) {
            return;
        }

        $bonusAmount = $kpi->getBonusAmount();

        if ($bonusAmount > 0) {
            $this->bonusService->createGenericBonus(
                $kpi->manager_id,
                'kpi_achievement',
                $kpi->id,
                $kpi->current_value,
                $bonusAmount / $kpi->current_value * 100, // Calculate percent
                $kpi->tenant_id,
                $kpi->vertical_id ?? 0,
                'kpi_achievement'
            );

            $this->logAction('manager_kpi', $kpiId, 'bonus_processed', [
                'bonus_amount' => $bonusAmount,
            ], $processedBy);
        }
    }

    /**
     * Auto-create KPIs for new manager
     */
    public function autoCreateKPIsForManager(int $managerId, int $tenantId, ?int $verticalId = null): void
    {
        $now = Carbon::now();
        $monthStart = $now->startOfMonth()->toDateString();
        $monthEnd = $now->endOfMonth()->toDateString();

        // Sales revenue KPI
        $this->createKPI([
            'manager_id' => $managerId,
            'tenant_id' => $tenantId,
            'vertical_id' => $verticalId,
            'kpi_type' => ManagerKPI::TYPE_SALES_REVENUE,
            'kpi_name' => 'Monthly Sales Revenue',
            'description' => 'Total sales revenue for the month',
            'target_value' => 1000000, // Default target: 1M RUB
            'target_unit' => 'RUB',
            'period_type' => ManagerKPI::PERIOD_MONTHLY,
            'period_start' => $monthStart,
            'period_end' => $monthEnd,
            'bonus_eligible' => true,
            'bonus_multiplier' => 1.0,
            'bonus_amount' => 50000, // 50K RUB bonus for achievement
        ]);

        // Task completion KPI
        $this->createKPI([
            'manager_id' => $managerId,
            'tenant_id' => $tenantId,
            'vertical_id' => $verticalId,
            'kpi_type' => ManagerKPI::TYPE_TASK_COMPLETION,
            'kpi_name' => 'Monthly Task Completion',
            'description' => 'Number of tasks completed',
            'target_value' => 50,
            'target_unit' => 'tasks',
            'period_type' => ManagerKPI::PERIOD_MONTHLY,
            'period_start' => $monthStart,
            'period_end' => $monthEnd,
            'bonus_eligible' => true,
            'bonus_multiplier' => 0.5,
            'bonus_amount' => 10000,
        ]);
    }
}
