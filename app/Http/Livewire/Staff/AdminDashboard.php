<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Modules\CatCRM\Application\DTOs\Staff\CreateEmployeeDTO;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * AdminDashboard — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Dashboard для администраторов с полным доступом к управлению HRM
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class AdminDashboard extends Component
{
    use WithAuditLogging;

    public array $employees = [];

    public array $stats = [];

    public array $recentActivity = [];

    public string $activeTab = 'overview';

    public string $search = '';

    public string $filterRole = '';

    public string $filterStatus = '';

    public bool $loading = true;

    public bool $showCreateModal = false;

    public array $createForm = [
        'first_name' => '',
        'last_name' => '',
        'middle_name' => '',
        'email' => '',
        'phone' => '',
        'position' => '',
        'department' => '',
        'role' => 'specialist',
        'manager_id' => '',
    ];

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadDashboard($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'dashboard_viewed',
            entityType: 'admin_dashboard',
            entityId: null,
            context: ['tab' => $this->activeTab],
        );
    }

    public function loadDashboard(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->loading = true;

        try {
            // Load employees list
            $this->employees = $this->getEmployeesList($staffService, $tenantId);

            // Load statistics
            $this->stats = $this->getStatistics($staffService, $tenantId);

            // Load recent activity
            $this->recentActivity = $this->getRecentActivity($staffService, $tenantId);
        } catch (\Exception $e) {
            $this->employees = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    private function getEmployeesList(CRMStaffIntegrationService $staffService, int $tenantId): array
    {
        // TODO: Implement getEmployeesList in CRMStaffIntegrationService
        return [
            'total' => 0,
            'data' => [],
        ];
    }

    private function getStatistics(CRMStaffIntegrationService $staffService, int $tenantId): array
    {
        // TODO: Implement getStatistics in CRMStaffIntegrationService
        return [
            'total_employees' => 0,
            'active_employees' => 0,
            'on_leave' => 0,
            'new_this_month' => 0,
        ];
    }

    private function getRecentActivity(CRMStaffIntegrationService $staffService, int $tenantId): array
    {
        // TODO: Implement getRecentActivity in CRMStaffIntegrationService
        return [];
    }

    public function createEmployee(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->validate([
            'createForm.first_name' => 'required|string|max:255',
            'createForm.last_name' => 'required|string|max:255',
            'createForm.email' => 'required|email|unique:users,email',
            'createForm.phone' => 'required|string|max:20',
            'createForm.role' => 'required|string',
        ]);

        try {
            $dto = new CreateEmployeeDTO(
                tenantId: $tenantId,
                userId: $user->id,
                firstName: $this->createForm['first_name'],
                lastName: $this->createForm['last_name'],
                middleName: $this->createForm['middle_name'] ?: null,
                email: $this->createForm['email'],
                phone: $this->createForm['phone'],
                position: $this->createForm['position'] ?: null,
                department: $this->createForm['department'] ?: null,
                role: $this->createForm['role'],
                managerId: $this->createForm['manager_id'] ?: null,
                slackId: null,
                teamsId: null,
            );

            $result = $staffService->createEmployee($dto, $user->id);

            $this->logCreated(
                userId: $user->id,
                tenantId: $tenantId,
                entityType: 'employee',
                entityId: $result['employee_id'] ?? null,
                context: [
                    'email' => $this->createForm['email'],
                    'role' => $this->createForm['role'],
                    'correlation_id' => $result['correlation_id'] ?? null,
                ],
            );

            $this->showCreateModal = false;
            $this->reset('createForm');
            $this->loadDashboard($staffService);

            $this->dispatch('employee-created', employeeId: $result['employee_id']);
        } catch (\Exception $e) {
            $this->logAction(
                userId: $this->user->id,
                tenantId: $tenantId,
                action: 'employee_creation_failed',
                entityType: 'employee',
                entityId: null,
                context: [
                    'error' => $e->getMessage(),
                    'form_data' => $this->createForm,
                ],
            );
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function deleteEmployee(int $employeeId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        try {
            // TODO: Implement deleteEmployee in CRMStaffIntegrationService
            $this->logDeleted(
                userId: $user->id,
                tenantId: $tenantId,
                entityType: 'employee',
                entityId: $employeeId,
                context: [],
            );
            $this->dispatch('employee-deleted', employeeId: $employeeId);
        } catch (\Exception $e) {
            $this->logAction(
                userId: $this->user->id,
                tenantId: $tenantId,
                action: 'employee_deletion_failed',
                entityType: 'employee',
                entityId: $employeeId,
                context: ['error' => $e->getMessage()],
            );
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function onboardEmployee(int $employeeId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $onboardingData = [
            'start_date' => now(),
            'mentor_id' => null,
            'goals' => [],
            'timeline' => '90_days',
            'required_courses' => [],
        ];

        try {
            $result = $staffService->onboardNewEmployee($tenantId, $employeeId, $onboardingData, $user->id);

            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'employee_onboarded',
                entityType: 'employee',
                entityId: $employeeId,
                context: [
                    'onboarding_data' => $onboardingData,
                    'correlation_id' => $result['correlation_id'] ?? null,
                ],
            );

            $this->dispatch('employee-onboarded', employeeId: $employeeId);
        } catch (\Exception $e) {
            $this->logAction(
                userId: $this->user->id,
                tenantId: $tenantId,
                action: 'employee_onboarding_failed',
                entityType: 'employee',
                entityId: $employeeId,
                context: ['error' => $e->getMessage()],
            );
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $user = auth()->user();
        $this->activeTab = $tab;

        $this->logAction(
            userId: $user?->id,
            tenantId: $user?->tenant_id,
            action: 'tab_changed',
            entityType: 'admin_dashboard',
            entityId: null,
            context: ['tab' => $tab],
        );
    }

    public function openCreateModal(): void
    {
        $user = auth()->user();
        $this->showCreateModal = true;

        $this->logAction(
            userId: $user?->id,
            tenantId: $user?->tenant_id,
            action: 'create_modal_opened',
            entityType: 'employee',
            entityId: null,
            context: [],
        );
    }

    public function closeCreateModal(): void
    {
        $user = auth()->user();
        $this->showCreateModal = false;
        $this->reset('createForm');

        $this->logAction(
            userId: $user?->id,
            tenantId: $user?->tenant_id,
            action: 'create_modal_closed',
            entityType: 'employee',
            entityId: null,
            context: [],
        );
    }

    public function refresh(): void
    {
        $user = auth()->user();
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadDashboard($staffService);

        $this->logAction(
            userId: $user?->id,
            tenantId: $user?->tenant_id,
            action: 'dashboard_refreshed',
            entityType: 'admin_dashboard',
            entityId: null,
            context: [],
        );

        $this->dispatch('dashboard-refreshed');
    }

    public function render()
    {
        return view('livewire.staff.admin-dashboard');
    }
}
