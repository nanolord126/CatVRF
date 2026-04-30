<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * ManagerDashboard — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Dashboard для менеджеров с аналитикой команды, лидербордом, прогнозом найма
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class ManagerDashboard extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $teamDynamics = [];

    public array $leaderboard = [];

    public array $hiringForecast = [];

    public array $scheduleOptimization = [];

    public string $period = 'weekly';

    public int $horizonMonths = 6;

    public int $teamId = null;

    public bool $loading = true;

    public string $activeTab = 'overview';

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadDashboard($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'dashboard_viewed',
            entityType: 'manager_dashboard',
            entityId: null,
            context: ['tab' => $this->activeTab],
        );
    }

    public function loadDashboard(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $managerId = $user->id;

        $this->loading = true;

        try {
            $dashboard = $staffService->getManagerDashboard(
                $tenantId,
                $managerId,
                [
                    'period' => $this->period,
                    'horizon_months' => $this->horizonMonths,
                    'team_id' => $this->teamId,
                ],
                $user->id
            );

            $this->teamDynamics = $dashboard['team_dynamics'] ?? [];
            $this->leaderboard = $dashboard['leaderboard'] ?? [];
            $this->hiringForecast = $dashboard['hiring_forecast'] ?? [];
            $this->scheduleOptimization = $dashboard['schedule_optimization'] ?? [];
        } catch (\Exception $e) {
            $this->teamDynamics = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function updatedPeriod(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadDashboard($staffService);
    }

    public function updatedHorizonMonths(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadDashboard($staffService);
    }

    public function analyzeTeam(CRMStaffIntegrationService $staffService): void
    {
        if (!$this->teamId) {
            return;
        }

        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->loading = true;

        try {
            $this->teamDynamics = $staffService->analyzeTeamDynamics($tenantId, $this->teamId, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'team_analyzed',
                entityType: 'team',
                entityId: $this->teamId,
                context: ['manager_id' => $managerId],
            );

            $this->dispatch('team-analyzed');
        } catch (\Exception $e) {
            $this->teamDynamics = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function optimizeSchedule(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->loading = true;

        try {
            $constraints = [
                'max_hours_per_week' => 40,
                'min_rest_between_shifts' => 11,
                'skill_requirements' => [],
            ];

            $this->scheduleOptimization = $staffService->optimizeSchedule($tenantId, $constraints, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'schedule_optimized',
                entityType: 'schedule',
                entityId: null,
                context: $constraints,
            );

            $this->dispatch('schedule-optimized');
        } catch (\Exception $e) {
            $this->scheduleOptimization = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function generateForecast(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->loading = true;

        try {
            $this->hiringForecast = $staffService->generateHiringForecast($tenantId, $this->horizonMonths, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'hiring_forecast_generated',
                entityType: 'hiring_forecast',
                entityId: null,
                context: ['horizon_months' => $this->horizonMonths],
            );

            $this->dispatch('forecast-generated');
        } catch (\Exception $e) {
            $this->hiringForecast = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function refresh(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadDashboard($staffService);
        $this->dispatch('dashboard-refreshed');
    }

    public function render()
    {
        return view('livewire.staff.manager-dashboard');
    }
}
