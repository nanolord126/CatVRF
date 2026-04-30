<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * LeaveManagement — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Компонент для управления отпусками
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class LeaveManagement extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $leaves = [];

    public string $filter = 'all';

    public string $statusFilter = 'all';

    public bool $loading = true;

    public bool $showModal = false;

    public array $form = [
        'employee_id' => '',
        'type' => 'vacation',
        'start_date' => '',
        'end_date' => '',
        'reason' => '',
    ];

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadLeaves($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'leave_management_viewed',
            entityType: 'leave_management',
            entityId: null,
            context: ['filter' => $this->filter],
        );
    }

    public function loadLeaves(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->loading = true;

        try {
            // TODO: Implement getLeaves in CRMStaffIntegrationService
            $this->leaves = [];
        } catch (\Exception $e) {
            $this->leaves = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function submitLeave(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenantId;
        $employeeId = (int) $this->form['employee_id'] ?: $user->id;

        $this->validate([
            'form.type' => 'required|string',
            'form.start_date' => 'required|date',
            'form.end_date' => 'required|date|after_or_equal:form.start_date',
        ]);

        try {
            $leaveData = [
                'type' => $this->form['type'],
                'start_date' => $this->form['start_date'],
                'end_date' => $this->form['end_date'],
                'reason' => $this->form['reason'],
            ];

            $result = $staffService->requestLeave($tenantId, $employeeId, $leaveData, $user->id);

            $this->showModal = false;
            $this->reset('form');
            $this->loadLeaves($staffService);

            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'leave_submitted',
                entityType: 'leave',
                entityId: $result['leave_id'] ?? null,
                context: array_merge($leaveData, ['employee_id' => $employeeId]),
            );

            $this->dispatch('leave-submitted', leaveId: $result['leave_id'] ?? null);
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
            $this->loadLeaves($staffService);
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
        // TODO: Implement rejectLeave
        $this->dispatch('leave-rejected', leaveId: $leaveId);
    }

    public function cancelLeave(int $leaveId, CRMStaffIntegrationService $staffService): void
    {
        // TODO: Implement cancelLeave
        $this->dispatch('leave-cancelled', leaveId: $leaveId);
    }

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset('form');
    }

    public function refresh(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadLeaves($staffService);
        $this->dispatch('refreshed');
    }

    public function render()
    {
        return view('livewire.staff.leave-management');
    }
}
