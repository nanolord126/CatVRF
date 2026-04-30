<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * StaffDashboard — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Dashboard для сотрудников с отображением метрик, достижений, графика
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class StaffDashboard extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $performance = [];

    public array $burnout = [];

    public array $achievements = [];

    public array $progress = [];

    public array $rank = [];

    public array $wellness = [];

    public string $activeTab = 'overview';

    public bool $loading = true;

    public int $refreshInterval = 30;

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadDashboard($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'dashboard_viewed',
            entityType: 'staff_dashboard',
            entityId: auth()->id(),
            context: ['tab' => $this->activeTab],
        );
    }

    public function loadDashboard(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        $this->loading = true;

        try {
            $profile = $staffService->getEmployeeProfile($tenantId, $employeeId, $user->id);
            $this->performance = $profile['performance_analysis'] ?? [];
            $this->burnout = $profile['burnout_prediction'] ?? [];
            $this->achievements = $profile['achievements'] ?? [];
            $this->progress = $profile['progress'] ?? [];
            $this->rank = $profile['rank'] ?? [];

            // Load wellness metrics
            $this->wellness = $staffService->getWorkLifeBalanceScore($tenantId, $employeeId, $user->id);
        } catch (\Exception $e) {
            $this->performance = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function analyzePerformance(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        $this->loading = true;

        try {
            $this->performance = $staffService->analyzePerformance($tenantId, $employeeId, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'performance_analyzed',
                entityType: 'performance_analysis',
                entityId: $employeeId,
                context: [],
            );

            $this->dispatch('performance-analyzed');
        } catch (\Exception $e) {
            $this->performance = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function predictBurnout(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        $this->loading = true;

        try {
            $this->burnout = $staffService->predictBurnoutRisk($tenantId, $employeeId, $user->id);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'burnout_predicted',
                entityType: 'burnout_prediction',
                entityId: $employeeId,
                context: [],
            );

            $this->dispatch('burnout-predicted');
        } catch (\Exception $e) {
            $this->burnout = ['error' => $e->getMessage()];
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
        return view('livewire.staff.staff-dashboard');
    }
}
