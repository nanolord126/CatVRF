<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ManagerClientAssignment;
use App\Traits\WithAuditLogging;
use App\Traits\WithTelemetry;
use Illuminate\Support\Facades\DB;

final class ManagerClientAssignmentService
{
    use WithAuditLogging;
    use WithTelemetry;

    /**
     * Assign manager to client (business)
     */
    public function assignManager(
        int $managerId,
        int $businessId,
        int $tenantId,
        ?int $verticalId = null,
        bool $isPrimary = false,
        ?string $notes = null
    ): ManagerClientAssignment {
        return DB::transaction(function () use (
            $managerId,
            $businessId,
            $tenantId,
            $verticalId,
            $isPrimary,
            $notes
        ) {
            // Unassign previous primary manager if this is primary
            if ($isPrimary) {
                ManagerClientAssignment::active()
                    ->where('business_id', $businessId)
                    ->where('tenant_id', $tenantId)
                    ->where('vertical_id', $verticalId)
                    ->where('is_primary', true)
                    ->update([
                        'is_primary' => false,
                        'unassigned_at' => now(),
                    ]);
            }

            $assignment = new ManagerClientAssignment([
                'manager_id' => $managerId,
                'business_id' => $businessId,
                'tenant_id' => $tenantId,
                'vertical_id' => $verticalId,
                'is_primary' => $isPrimary,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            $assignment->save();

            $this->logCreated('manager_client_assignment', $assignment->id, [
                'manager_id' => $managerId,
                'business_id' => $businessId,
                'vertical_id' => $verticalId,
                'is_primary' => $isPrimary,
            ], $managerId);

            return $assignment;
        });
    }

    /**
     * Unassign manager from client
     */
    public function unassignManager(int $assignmentId, int $unassignedBy): ManagerClientAssignment
    {
        $assignment = ManagerClientAssignment::findOrFail($assignmentId);
        $assignment->unassign();

        $this->logAction('manager_client_assignment', $assignmentId, 'unassigned', [], $unassignedBy);

        return $assignment;
    }

    /**
     * Get active manager for client
     */
    public function getActiveManager(int $businessId, int $tenantId, ?int $verticalId = null): ?ManagerClientAssignment
    {
        $query = ManagerClientAssignment::active()
            ->where('business_id', $businessId)
            ->where('tenant_id', $tenantId);

        if ($verticalId) {
            $query->where(function ($q) use ($verticalId) {
                $q->where('vertical_id', $verticalId)
                    ->orWhereNull('vertical_id');
            });
        } else {
            $query->whereNull('vertical_id');
        }

        return $query->orderBy('is_primary', 'desc')->first();
    }

    /**
     * Get all clients for manager
     */
    public function getManagerClients(int $managerId, int $tenantId, bool $activeOnly = true)
    {
        $query = ManagerClientAssignment::with('business')
            ->where('manager_id', $managerId)
            ->where('tenant_id', $tenantId);

        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('is_primary', 'desc')->paginate(50);
    }

    /**
     * Get client assignment history
     */
    public function getClientHistory(int $businessId, int $tenantId)
    {
        return ManagerClientAssignment::with('manager')
            ->where('business_id', $businessId)
            ->where('tenant_id', $tenantId)
            ->orderBy('assigned_at', 'desc')
            ->get();
    }
}
