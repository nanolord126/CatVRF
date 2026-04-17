<?php declare(strict_types=1);

namespace App\Services\HR;

use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class LeaveManagementService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly Cache $cache,
    ) {}

    /**
     * Request leave
     */
    public function requestLeave(
        int $tenantId,
        int $employeeId,
        string $leaveType,
        \DateTime $startDate,
        \DateTime $endDate,
        string $reason,
        string $correlationId,
        array $metadata = []
    ): int {
        $this->fraud->check([
            'operation_type' => 'leave_request',
            'employee_id' => $employeeId,
            'leave_type' => $leaveType,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $tenantId,
            $employeeId,
            $leaveType,
            $startDate,
            $endDate,
            $reason,
            $correlationId,
            $metadata
        ) {
            $days = $startDate->diff($endDate)->days + 1;

            // Check leave balance
            $balance = $this->getLeaveBalance($employeeId, $leaveType, $correlationId);
            if ($balance < $days) {
                throw new \RuntimeException('Insufficient leave balance');
            }

            $leaveId = $this->db->table('leaves')->insertGetId([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'leave_type' => $leaveType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'reason' => $reason,
                'status' => 'pending',
                'correlation_id' => $correlationId,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record(
                action: 'leave_requested',
                subjectType: 'leave',
                subjectId: $leaveId,
                newValues: [
                    'employee_id' => $employeeId,
                    'leave_type' => $leaveType,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days' => $days,
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('Leave requested', [
                'leave_id' => $leaveId,
                'employee_id' => $employeeId,
                'leave_type' => $leaveType,
                'days' => $days,
                'correlation_id' => $correlationId,
            ]);

            return $leaveId;
        });
    }

    /**
     * Approve leave request
     */
    public function approveLeave(
        int $leaveId,
        int $approvedBy,
        string $correlationId,
        ?string $notes = null
    ): bool {
        $this->fraud->check([
            'operation_type' => 'leave_approve',
            'leave_id' => $leaveId,
            'approved_by' => $approvedBy,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($leaveId, $approvedBy, $correlationId, $notes) {
            $leave = $this->db->table('leaves')->where('id', $leaveId)->first();

            if (!$leave) {
                throw new \RuntimeException('Leave not found');
            }

            if ($leave->status !== 'pending') {
                throw new \RuntimeException('Leave already processed');
            }

            // Deduct from balance
            $this->updateLeaveBalance($leave->employee_id, $leave->leave_type, -$leave->days, $correlationId);

            $updated = $this->db->table('leaves')
                ->where('id', $leaveId)
                ->update([
                    'status' => 'approved',
                    'approved_by' => $approvedBy,
                    'approved_at' => now(),
                    'notes' => $notes,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                $this->audit->record(
                    action: 'leave_approved',
                    subjectType: 'leave',
                    subjectId: $leaveId,
                    newValues: [
                        'approved_by' => $approvedBy,
                        'approved_at' => now()->format('Y-m-d H:i:s'),
                    ],
                    correlationId: $correlationId,
                );
            }

            return $updated > 0;
        });
    }

    /**
     * Reject leave request
     */
    public function rejectLeave(
        int $leaveId,
        int $rejectedBy,
        string $rejectionReason,
        string $correlationId
    ): bool {
        $this->fraud->check([
            'operation_type' => 'leave_reject',
            'leave_id' => $leaveId,
            'rejected_by' => $rejectedBy,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($leaveId, $rejectedBy, $rejectionReason, $correlationId) {
            $updated = $this->db->table('leaves')
                ->where('id', $leaveId)
                ->update([
                    'status' => 'rejected',
                    'rejected_by' => $rejectedBy,
                    'rejection_reason' => $rejectionReason,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                $this->audit->record(
                    action: 'leave_rejected',
                    subjectType: 'leave',
                    subjectId: $leaveId,
                    newValues: [
                        'rejected_by' => $rejectedBy,
                        'rejection_reason' => $rejectionReason,
                    ],
                    correlationId: $correlationId,
                );
            }

            return $updated > 0;
        });
    }

    /**
     * Get leave balance for employee
     */
    public function getLeaveBalance(
        int $employeeId,
        string $leaveType,
        string $correlationId
    ): int {
        $cacheKey = "leave_balance:{$employeeId}:{$leaveType}";

        return $this->cache->remember($cacheKey, 3600, function () use ($employeeId, $leaveType) {
            $balance = $this->db->table('leave_balances')
                ->where('employee_id', $employeeId)
                ->where('leave_type', $leaveType)
                ->value('balance');

            return $balance ?? 0;
        });
    }

    /**
     * Update leave balance
     */
    private function updateLeaveBalance(
        int $employeeId,
        string $leaveType,
        int $days,
        string $correlationId
    ): void {
        $this->db->table('leave_balances')
            ->updateOrInsert(
                ['employee_id' => $employeeId, 'leave_type' => $leaveType],
                [
                    'balance' => $this->db->raw("GREATEST(0, COALESCE(balance, 0) + {$days})"),
                    'updated_at' => now(),
                ]
            );
    }

    /**
     * Get employee leave history
     */
    public function getLeaveHistory(
        int $employeeId,
        ?string $status = null,
        string $correlationId
    ): array {
        $query = $this->db->table('leaves')
            ->where('employee_id', $employeeId);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Accrue annual leave (scheduled job)
     */
    public function accrueAnnualLeave(
        int $tenantId,
        string $correlationId
    ): int {
        $this->fraud->check([
            'operation_type' => 'leave_accrual',
            'tenant_id' => $tenantId,
            'correlation_id' => $correlationId,
        ]);

        $accrued = 0;

        $employees = $this->db->table('employees')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($employees as $employee) {
            $monthlyAccrual = $employee->annual_leave_days / 12;
            $this->updateLeaveBalance($employee->id, 'annual', (int) $monthlyAccrual, $correlationId);
            $accrued++;
        }

        $this->logger->channel('audit')->info('Annual leave accrued', [
            'tenant_id' => $tenantId,
            'employees_count' => $accrued,
            'correlation_id' => $correlationId,
        ]);

        return $accrued;
    }
}
