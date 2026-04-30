<?php

declare(strict_types=1);

namespace Modules\CatCRM\Infrastructure\Repositories;

use Modules\CatCRM\Domain\Staff\Repositories\LeaveRepositoryInterface;
use Modules\CatCRM\Domain\Staff\Leave;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveId;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveStatus;
use Modules\CatCRM\Domain\Staff\ValueObjects\LeaveType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * EloquentLeaveRepository — Layer 7: Repository Implementation
 */
final class EloquentLeaveRepository implements LeaveRepositoryInterface
{
    public function findById(LeaveId $id): ?Leave
    {
        $record = DB::table('staff_leaves')->where('id', $id->value)->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function findByEmployee(int $tenantId, int $employeeId): array
    {
        $records = DB::table('staff_leaves')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findPendingByEmployee(int $tenantId, int $employeeId): array
    {
        $records = DB::table('staff_leaves')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('status', 'pending')
            ->get();

        return array_map([$this, 'mapToEntity'], $records->toArray());
    }

    public function findActiveByEmployee(int $tenantId, int $employeeId): ?Leave
    {
        $record = DB::table('staff_leaves')
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
        
        if (!$record) {
            return null;
        }

        return $this->mapToEntity($record);
    }

    public function save(Leave $leave): bool
    {
        $data = [
            'tenant_id' => $leave->tenantId,
            'employee_id' => $leave->employeeId,
            'type' => $leave->type->value,
            'status' => $leave->status->value,
            'start_date' => $leave->startDate->toDateTimeString(),
            'end_date' => $leave->endDate->toDateTimeString(),
            'reason' => $leave->reason,
            'approved_by' => $leave->approvedBy,
            'approved_at' => $leave->approvedAt?->toDateTimeString(),
            'reject_reason' => $leave->rejectReason,
            'metadata' => json_encode($leave->metadata),
            'updated_at' => $leave->updatedAt->toDateTimeString(),
        ];

        if ($leave->id->value === 0) {
            $data['created_at'] = $leave->createdAt->toDateTimeString();
            $id = DB::table('staff_leaves')->insertGetId($data);
            return $id > 0;
        } else {
            return DB::table('staff_leaves')
                ->where('id', $leave->id->value)
                ->update($data) > 0;
        }
    }

    public function delete(LeaveId $id): bool
    {
        return DB::table('staff_leaves')
            ->where('id', $id->value)
            ->delete() > 0;
    }

    private function mapToEntity(array $record): Leave
    {
        return new Leave(
            id: LeaveId::fromInt((int) $record['id']),
            tenantId: (int) $record['tenant_id'],
            employeeId: (int) $record['employee_id'],
            type: LeaveType::from($record['type']),
            status: LeaveStatus::from($record['status']),
            startDate: CarbonImmutable::parse($record['start_date']),
            endDate: CarbonImmutable::parse($record['end_date']),
            reason: $record['reason'],
            approvedBy: $record['approved_by'] ? (int) $record['approved_by'] : null,
            approvedAt: $record['approved_at'] ? CarbonImmutable::parse($record['approved_at']) : null,
            rejectReason: $record['reject_reason'] ?? null,
            metadata: json_decode($record['metadata'] ?? '{}', true),
            createdAt: CarbonImmutable::parse($record['created_at']),
            updatedAt: CarbonImmutable::parse($record['updated_at']),
        );
    }
}
