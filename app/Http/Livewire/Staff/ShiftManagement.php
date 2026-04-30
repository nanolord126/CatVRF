<?php

declare(strict_types=1);

namespace App\Http\Livewire\Staff;

use Livewire\Component;
use Modules\CatCRM\Application\Services\Staff\CRMStaffIntegrationService;
use Livewire\Attributes\Layout;
use App\Traits\WithAuditLogging;
use App\Services\AuditService;

/**
 * ShiftManagement — Layer 1: Presentation/UI Layer (Livewire Component)
 *
 * Компонент для управления сменами
 * Part of 9-layer architecture
 */
#[Layout('layouts.app')]
final class ShiftManagement extends Component
{
    use WithAuditLogging;

    public function __construct(
        public AuditService $auditService,
    ) {
        $this->correlationId = $this->generateCorrelationId();
    }
    public array $shifts = [];

    public string $view = 'week';

    public string $filterEmployee = '';

    public bool $loading = true;

    public bool $showCreateModal = false;

    public bool $showSubstituteModal = false;

    public array $createForm = [
        'employee_id' => '',
        'start_date' => '',
        'end_date' => '',
        'shift_type' => 'regular',
    ];

    public array $substituteForm = [
        'shift_id' => '',
        'substitute_id' => '',
    ];

    public function mount(CRMStaffIntegrationService $staffService): void
    {
        $this->loadShifts($staffService);
        $this->logAction(
            userId: auth()->id(),
            tenantId: auth()->user()?->tenant_id,
            action: 'shift_management_viewed',
            entityType: 'shift_management',
            entityId: null,
            context: ['view' => $this->view],
        );
    }

    public function loadShifts(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->loading = true;

        try {
            // TODO: Implement getShifts in CRMStaffIntegrationService
            $this->shifts = [];
        } catch (\Exception $e) {
            $this->shifts = ['error' => $e->getMessage()];
        } finally {
            $this->loading = false;
        }
    }

    public function createShift(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        $this->validate([
            'createForm.employee_id' => 'required|integer',
            'createForm.start_date' => 'required|date',
            'createForm.end_date' => 'required|date|after:createForm.start_date',
            'createForm.shift_type' => 'required|string',
        ]);

        try {
            $shiftData = [
                'employee_id' => (int) $this->createForm['employee_id'],
                'start_date' => $this->createForm['start_date'],
                'end_date' => $this->createForm['end_date'],
                'shift_type' => $this->createForm['shift_type'],
            ];

            $result = $staffService->createShift($tenantId, $shiftData, $user->id);

            $this->showCreateModal = false;
            $this->reset('createForm');
            $this->loadShifts($staffService);

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

    public function assignEmployee(int $shiftId, int $employeeId, CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        try {
            $staffService->assignEmployeeToShift($tenantId, $shiftId, $employeeId, $user->id);
            $this->loadShifts($staffService);
            $this->dispatch('employee-assigned', shiftId: $shiftId, employeeId: $employeeId);
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
            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'substitute_searched',
                entityType: 'shift',
                entityId: $shiftId,
                context: $criteria,
            );

            $this->dispatch('substitutes-found', shiftId: $shiftId, substitutes: $result);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function assignSubstitute(CRMStaffIntegrationService $staffService): void
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $shiftId = (int) $this->substituteForm['shift_id'];
        $substituteId = (int) $this->substituteForm['substitute_id'];

        try {
            $staffService->assignSubstitute($tenantId, $shiftId, $substituteId, $user->id);

            $this->showSubstituteModal = false;
            $this->reset('substituteForm');
            $this->loadShifts($staffService);

            $this->logAction(
                userId: $user->id,
                tenantId: $tenantId,
                action: 'substitute_assigned',
                entityType: 'shift',
                entityId: $shiftId,
                context: ['substitute_id' => $substituteId],
            );

            $this->dispatch('substitute-assigned', shiftId: $shiftId, substituteId: $substituteId);
        } catch (\Exception $e) {
            $this->dispatch('error', message: $e->getMessage());
        }
    }

    public function deleteShift(int $shiftId, CRMStaffIntegrationService $staffService): void
    {
        // TODO: Implement deleteShift in CRMStaffIntegrationService
        $this->dispatch('shift-deleted', shiftId: $shiftId);
    }

    public function setView(string $view): void
    {
        $this->view = $view;
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset('createForm');
    }

    public function openSubstituteModal(int $shiftId): void
    {
        $this->substituteForm['shift_id'] = $shiftId;
        $this->showSubstituteModal = true;
    }

    public function closeSubstituteModal(): void
    {
        $this->showSubstituteModal = false;
        $this->reset('substituteForm');
    }

    public function refresh(): void
    {
        $staffService = app(CRMStaffIntegrationService::class);
        $this->loadShifts($staffService);
        $this->dispatch('refreshed');
    }

    public function render()
    {
        return view('livewire.staff.shift-management');
    }
}
