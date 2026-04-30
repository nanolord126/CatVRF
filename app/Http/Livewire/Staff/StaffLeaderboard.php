<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * StaffLeaderboard — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Лидерборд сотрудников с геймификацией
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class StaffLeaderboard extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $leaderboard = [];

    public array $userRank = [];

    public string $period = 'weekly';

    public string $category = 'all';

    public bool $loading = true;

    public int $refreshInterval = 60;

    public array $periods = [
        'daily' => 'День',
        'weekly' => 'Неделя',
        'monthly' => 'Месяц',
        'quarterly' => 'Квартал',
        'yearly' => 'Год',
    ];

    public array $categories = [
        'all' => 'Все',
        'sales' => 'Продажи',
        'performance' => 'Производительность',
        'teamwork' => 'Командная работа',
        'innovation' => 'Инновации',
        'customer_service' => 'Обслуживание клиентов',
    ];

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadLeaderboard($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'leaderboard_viewed',
            entityType: 'leaderboard',
            entityId: null,
            context: ['period' => $this->period, 'category' => $this->category],
        );
    }

    public function loadLeaderboard(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $user->id;

        $this->loading = true;

        try {
            $this->leaderboard = $staffService->getLeaderboard($tenantId, $this->period, $user->id);
            $this->userRank = $staffService->getEmployeeRank($tenantId, $employeeId, $user->id);
        } catch (\Exception $e) {
            $this->leaderboard = ['error' => $e->getMessage()];
            $this->userRank = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadLeaderboard($staffService);
        $this->logAction(
            userId: Auth::id(),
            tenantId: Auth::user()?->tenant_id,
            action: 'leaderboard_period_changed',
            entityType: 'leaderboard',
            entityId: null,
            context: ['period' => $period],
        );

        $this->dispatch('period-changed', period: $period);
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'leaderboard_category_changed',
            entityType: 'leaderboard',
            entityId: null,
            context: ['category' => $category],
        );

        $this->dispatch('category-changed', category: $category);
    }

    public function refresh(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadLeaderboard($staffService);
        $this->dispatch('leaderboard-refreshed');
    }

    public function viewEmployeeProfile(int $employeeId): void
    {
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'employee_profile_viewed',
            entityType: 'employee',
            entityId: $employeeId,
            context: ['source' => 'leaderboard'],
        );

        $this->dispatch('view-employee', employeeId: $employeeId);
    }

    public function sendGratitude(int $employeeId): void
    {
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'gratitude_modal_opened',
            entityType: 'gratitude',
            entityId: $employeeId,
            context: ['source' => 'leaderboard'],
        );

        $this->dispatch('open-gratitude-modal', employeeId: $employeeId);
    }

    public function render()
    {
        return view('livewire.staff.staff-leaderboard');
    }
}
