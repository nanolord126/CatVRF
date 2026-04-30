<?php

declare(strict_types=1);

namespace Modules\CatCRM\Application\Services;

use Modules\CatCRM\Domain\Entities\ManagerKPI;
use Modules\CatCRM\Domain\Entities\Task;
use App\Services\Security\AuditService;
use App\Traits\WithAuditLogging;
use Carbon\Carbon;

/**
 * ManagerKPI Service — Сервис для управления KPI менеджеров
 */
final class ManagerKPIService
{
    use WithAuditLogging;

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Создать KPI период для менеджера
     */
    public function createKPIPeriod(array $data): ManagerKPI
    {
        $kpi = ManagerKPI::create([
            'tenant_id' => $data['tenant_id'],
            'business_group_id' => $data['business_group_id'] ?? null,
            'manager_id' => $data['manager_id'],
            'vertical_id' => $data['vertical_id'] ?? null,
            'period_type' => $data['period_type'] ?? 'monthly',
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'targets' => $data['targets'] ?? $this->getDefaultTargets(),
            'actuals' => [],
            'score' => 0,
            'status' => 'pending',
            'business_type' => $data['business_type'] ?? 'both',
            'tasks_assigned' => 0,
            'tasks_completed' => 0,
            'tasks_on_time' => 0,
            'tasks_overdue' => 0,
            'correlation_id' => $this->correlationId,
        ]);

        $this->logAction('kpi_period_created', 'ManagerKPI', $kpi->id, [
            'manager_id' => $kpi->manager_id,
            'period_type' => $kpi->period_type,
            'period_start' => $kpi->period_start,
            'period_end' => $kpi->period_end,
        ], $kpi->manager_id, $kpi->tenant_id);

        return $kpi;
    }

    /**
     * Получить активный KPI период для менеджера
     */
    public function getActiveKPI(int $managerId, int $tenantId): ?ManagerKPI
    {
        return ManagerKPI::byTenant($tenantId)
            ->byManager($managerId)
            ->active()
            ->orderBy('period_end', 'desc')
            ->first();
    }

    /**
     * Обновить KPI при завершении задачи
     */
    public function updateKPIOnTaskCompletion(Task $task): void
    {
        if (!$task->kpi_tracked || !$task->assigned_to_id) {
            return;
        }

        $kpi = $this->getActiveKPI($task->assigned_to_id, $task->tenant_id);
        
        if (!$kpi) {
            return;
        }

        // Проверяем, что задача в периоде KPI
        $taskDate = $task->completed_at ?? $task->due_date;
        if (!$taskDate || !$taskDate->between($kpi->period_start, $kpi->period_end)) {
            return;
        }

        $kpi->tasks_completed++;
        
        if ($task->completed_at && $task->due_date && $task->completed_at <= $task->due_date) {
            $kpi->tasks_on_time++;
        }

        // Обновляем actuals
        $actuals = $kpi->actuals ?? [];
        $actuals['tasks_completed'] = $kpi->tasks_completed;
        $actuals['tasks_on_time'] = $kpi->tasks_on_time;
        $kpi->actuals = $actuals;

        $kpi->calculateScore();
        $kpi->save();

        $this->logAction('kpi_updated_on_task', 'ManagerKPI', $kpi->id, [
            'task_id' => $task->id,
            'tasks_completed' => $kpi->tasks_completed,
            'score' => $kpi->score,
        ], $kpi->manager_id, $kpi->tenant_id);
    }

    /**
     * Обновить KPI при назначении задачи
     */
    public function updateKPIOnTaskAssignment(Task $task): void
    {
        if (!$task->kpi_tracked || !$task->assigned_to_id) {
            return;
        }

        $kpi = $this->getActiveKPI($task->assigned_to_id, $task->tenant_id);
        
        if (!$kpi) {
            return;
        }

        // Проверяем, что задача в периоде KPI
        $taskDate = $task->due_date ?? $task->created_at;
        if (!$taskDate || !$taskDate->between($kpi->period_start, $kpi->period_end)) {
            return;
        }

        $kpi->tasks_assigned++;
        $kpi->save();

        $this->logAction('kpi_updated_on_assignment', 'ManagerKPI', $kpi->id, [
            'task_id' => $task->id,
            'tasks_assigned' => $kpi->tasks_assigned,
        ], $kpi->manager_id, $kpi->tenant_id);
    }

    /**
     * Получить KPI статистику для менеджера
     */
    public function getManagerKPIStats(int $managerId, int $tenantId, string $periodType = 'monthly'): array
    {
        $kpiRecords = ManagerKPI::byTenant($tenantId)
            ->byManager($managerId)
            ->byPeriodType($periodType)
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        return [
            'average_score' => $kpiRecords->avg('score'),
            'total_tasks_assigned' => $kpiRecords->sum('tasks_assigned'),
            'total_tasks_completed' => $kpiRecords->sum('tasks_completed'),
            'total_tasks_on_time' => $kpiRecords->sum('tasks_on_time'),
            'total_tasks_overdue' => $kpiRecords->sum('tasks_overdue'),
            'completion_rate' => $kpiRecords->sum('tasks_assigned') > 0 
                ? ($kpiRecords->sum('tasks_completed') / $kpiRecords->sum('tasks_assigned')) * 100 
                : 0,
            'on_time_rate' => $kpiRecords->sum('tasks_completed') > 0 
                ? ($kpiRecords->sum('tasks_on_time') / $kpiRecords->sum('tasks_completed')) * 100 
                : 0,
            'periods' => $kpiRecords,
        ];
    }

    /**
     * Завершить KPI период
     */
    public function completeKPIPeriod(ManagerKPI $kpi): ManagerKPI
    {
        $kpi->complete();

        $this->logAction('kpi_period_completed', 'ManagerKPI', $kpi->id, [
            'final_score' => $kpi->score,
            'tasks_completed' => $kpi->tasks_completed,
        ], $kpi->manager_id, $kpi->tenant_id);

        return $kpi->fresh();
    }

    /**
     * Получить дефолтные цели KPI
     */
    private function getDefaultTargets(): array
    {
        return [
            'tasks_completed' => [
                'value' => 50,
                'weight' => 1.0,
                'description' => 'Количество выполненных задач',
            ],
            'tasks_on_time' => [
                'value' => 45,
                'weight' => 1.2,
                'description' => 'Количество задач выполненных вовремя',
            ],
            'customer_satisfaction' => [
                'value' => 4.5,
                'weight' => 0.8,
                'description' => 'Средняя оценка удовлетворенности клиентов',
            ],
        ];
    }

    /**
     * Обновить цели KPI
     */
    public function updateKPITargets(ManagerKPI $kpi, array $targets): ManagerKPI
    {
        $kpi->targets = $targets;
        $kpi->save();

        $this->logAction('kpi_targets_updated', 'ManagerKPI', $kpi->id, [
            'targets' => $targets,
        ], $kpi->manager_id, $kpi->tenant_id);

        return $kpi->fresh();
    }
}
