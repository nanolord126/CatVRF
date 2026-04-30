<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * HRDashboard — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Dashboard для HR с управлением персоналом, отпусками, графиком
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class HRDashboard extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $leaveRequests = [];

    public array $shiftSchedule = [];

    public array $hiringPipeline = [];

    public array $wellnessOverview = [];

    public string $activeTab = 'leaves';

    public string $leaveFilter = 'pending';

    public string $dateRange = 'week';

    public bool $loading = true;

    public bool $showLeaveModal = false;

    public bool $showShiftModal = false;

    public array $leaveForm = [
        'employee_id' => '',
        'type' => 'vacation',
        'start_date' => '',
        'end_date' => '',
        'reason' => '',
    ];

    public array $shiftForm = [
        'employee_id' => '',
        'start_date' => '',
        'end_date' => '',
        'shift_type' => 'regular',
    ];

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadDashboard($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'dashboard_viewed',
            entityType: 'hr_dashboard',
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
            $this->loadLeaveRequests($staffService, $tenantId);
            $this->loadShiftSchedule($staffService, $tenantId);
            $this->loadHiringPipeline($staffService, $tenantId);
            $this->loadWellnessOverview($staffService, $tenantId);
        } catch (\Exception $e) {
            $this->leaveRequests = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    private function loadLeaveRequests(CRMStaffIntegrationService $staffService, int $tenantId): void
    {
        // TODO: Implement getLeaveRequests in CRMStaffIntegrationService
        $this->leaveRequests = [
            'pending' => [],
            'approved' => [],
            'rejected' => [],
        ];
    }

    private function loadShiftSchedule(CRMStaffIntegrationService $staffService, int $tenantId): void
    {
        // TODO: Implement getShiftSchedule in CRMStaffIntegrationService
        $this->shiftSchedule = [];
    }

    private function loadHiringPipeline(CRMStaffIntegrationService $staffService, int $tenantId): void
    {
        // TODO: Implement getHiringPipeline in CRMStaffIntegrationService
        $this->hiringPipeline = [
            'candidates' => 0,
            'interviews' => 0,
            'offers' => 0,
            'hired' => 0,
        ];
    }

    private function loadWellnessOverview(CRMStaffIntegrationService $staffService, int $tenantId): void
    {
        // TODO: Implement getWellnessOverview in CRMStaffIntegrationService
        $this->wellnessOverview = [
            'high_stress' => 0,
            'moderate_stress' => 0,
            'low_stress' => 0,
        ];
    }

    public function requestLeave(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $employeeId = $this->leaveForm['employee_id'] ?: $user->id;

        $this->validate([
            'leaveForm.employee_id' => 'required|integer',
            'leaveForm.type' => 'required|string',
            'leaveForm.start_date' => 'required|date',
            'leaveForm.end_date' => 'required|date|after_or_equal:leaveForm.start_date',
        ]);

        try {
            $leaveData = [
                'type' => $this->leaveForm['type'],
                'start_date' => $this->leaveForm['start_date'],
                'end_date' => $this->leaveForm['end_date'],
                'reason' => $this->leaveForm['reason'],
            ];

            $result = $staffService->requestLeave($tenantId, (int) $employeeId, $leaveData, $user->id);

            $this->showLeaveModal = false;
            $this->reset('leaveForm');
            $this->loadLeaveRequests($staffService, $tenantId);

            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'leave_requested',
                entityType: 'leave',
                entityId: $result['leave_id'] ?? null,
                context: array_merge($leaveData, ['employee_id' => $employeeId]),
            );

            $this->dispatch('leave-requested', leaveId: $result['leave_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function approveLeave(int $leaveId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        try {
            $staffService->approveLeave($tenantId, $leaveId, $user->id, $user->id);
            $this->loadLeaveRequests($staffService, $tenantId);
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'leave_approved',
                entityType: 'leave',
                entityId: $leaveId,
                context: ['approver_id' => $user->id],
            );

            $this->dispatch('leave-approved', leaveId: $leaveId);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function rejectLeave(int $leaveId, CRMStaffIntegrationService $staffService): void
    {
        // TODO: Implement rejectLeave in CRMStaffIntegrationService
        $this->dispatch('leave-rejected', leaveId: $leaveId);
    }

    public function createShift(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->validate([
            'shiftForm.employee_id' => 'required|integer',
            'shiftForm.start_date' => 'required|date',
            'shiftForm.end_date' => 'required|date|after:shiftForm.start_date',
        ]);

        try {
            $shiftData = [
                'employee_id' => (int) $this->shiftForm['employee_id'],
                'start_date' => $this->shiftForm['start_date'],
                'end_date' => $this->shiftForm['end_date'],
                'shift_type' => $this->shiftForm['shift_type'],
            ];

            $result = $staffService->createShift($tenantId, $shiftData, $user->id);

            $this->showShiftModal = false;
            $this->reset('shiftForm');
            $this->loadShiftSchedule($staffService, $tenantId);

            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'shift_created',
                entityType: 'shift',
                entityId: $result['shift_id'] ?? null,
                context: $shiftData,
            );

            $this->dispatch('shift-created', shiftId: $result['shift_id'] ?? null);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function findSubstitute(int $shiftId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        try {
            $criteria = [
                'skill_requirements' => [],
                'availability' => true,
            ];

            $result = $staffService->findSubstitute($tenantId, $shiftId, $criteria, $user->id);
            $this->dispatch('substitutes-found', shiftId: $shiftId, substitutes: $result);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function recordWellness(int $employeeId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        try {
            $metrics = [
                'stress_level' => 5,
                'sleep_hours' => 7,
                'work_hours' => 8,
                'physical_activity_hours' => 1,
            ];

            $staffService->recordWellnessMetrics($tenantId, $employeeId, $metrics, $user->id);
            $this->loadWellnessOverview($staffService, $tenantId);
            $this->dispatch('wellness-recorded', employeeId: $employeeId);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openLeaveModal(): void
    {
        $this->showLeaveModal = true;
    }

    public function closeLeaveModal(): void
    {
        $this->showLeaveModal = false;
        $this->reset('leaveForm');
    }

    public function openShiftModal(): void
    {
        $this->showShiftModal = true;
    }

    public function closeShiftModal(): void
    {
        $this->showShiftModal = false;
        $this->reset('shiftForm');
    }

    public function refresh(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadDashboard($staffService);
        $this->dispatch('dashboard-refreshed');
    }

    public function render()
    {
        return view('livewire.staff.hr-dashboard');
    }
}
