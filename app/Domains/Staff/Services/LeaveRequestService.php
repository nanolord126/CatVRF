<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Models\LeaveRequest;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Traits\WithAuditLogging;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Carbon;

/**
 * LeaveRequestService — управление отпусками сотрудников.
 * Создание, одобрение, отклонение, отслеживание баланса отпусков.
 */
final readonly class LeaveRequestService
{use WithAuditLogging;

    
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать запрос на отпуск с проверкой баланса
     */
    public function createLeaveRequest(array $data): LeaveRequest
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'leave_request_create',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($data, $correlationId): LeaveRequest {
            // Map staff_id to employee_id
            $employeeId = $data['employee_id'] ?? $data['staff_id'] ?? null;
            unset($data['staff_id']);
            $data['employee_id'] = $employeeId;

            // Map type to leave_type
            $leaveType = $data['leave_type'] ?? $data['type'] ?? 'annual';
            unset($data['type']);
            $data['leave_type'] = $leaveType;

            // Проверка баланса отпусков
            $balance = $this->getLeaveBalance($employeeId);
            $requestedDays = $this->calculateLeaveDays($data['start_date'], $data['end_date']);

            if ($requestedDays > $balance['available']) {
                throw new \InvalidArgumentException('Insufficient leave balance');
            }

            // Проверка перекрытия с другими отпусками
            $hasOverlap = $this->checkLeaveOverlap(
                $employeeId,
                $data['start_date'],
                $data['end_date']
            );

            if ($hasOverlap) {
                throw new \InvalidArgumentException('Leave request overlaps with existing leave');
            }

            $leaveRequest = LeaveRequest::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()?->id ?? $data['tenant_id'] ?? null,
                'status' => 'pending',
                'days' => $requestedDays,
            ]));

            $this->logger->$this->logger->info('Leave request created', [
                'id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'correlation_id' => $correlationId,
                'tenant_id' => $leaveRequest->tenant_id,
                'days_requested' => $requestedDays,
            ]);

            $this->audit->log(
                'leave_request_created',
                LeaveRequest::class,
                $leaveRequest->id,
                [],
                $leaveRequest->toArray(),
                $correlationId
            );

            return $leaveRequest;
        });
    }

    /**
     * Одобрить запрос на отпуск
     */
    public function approveLeaveRequest(LeaveRequest $leaveRequest, string $approvedBy, ?string $notes = null): LeaveRequest
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'leave_request_approve',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($leaveRequest, $approvedBy, $notes, $correlationId): LeaveRequest {
            if ($leaveRequest->status !== 'pending') {
                throw new \InvalidArgumentException('Leave request is not in pending status');
            }

            // Повторная проверка баланса перед одобрением
            $balance = $this->getLeaveBalance($leaveRequest->employee_id);

            if ($leaveRequest->days > $balance['available']) {
                throw new \InvalidArgumentException('Insufficient leave balance at approval time');
            }

            $old = $leaveRequest->toArray();

            $leaveRequest->update([
                'status' => 'approved',
                'approved_by' => $approvedBy,
                'approved_at' => CarbonImmutable::now(),
                'approval_notes' => $notes,
                'correlation_id' => $correlationId,
            ]);

            // Вычесть дни из баланса
            $this->updateLeaveBalance($leaveRequest->employee_id, -$leaveRequest->days);

            $this->logger->$this->logger->info('Leave request approved', [
                'id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'correlation_id' => $correlationId,
                'approved_by' => $approvedBy,
                'days' => $leaveRequest->days,
            ]);

            $this->audit->log(
                'leave_request_approved',
                LeaveRequest::class,
                $leaveRequest->id,
                $old,
                $leaveRequest->fresh()->toArray(),
                $correlationId
            );

            return $leaveRequest->fresh();
        });
    }

    /**
     * Отклонить запрос на отпуск
     */
    public function rejectLeaveRequest(LeaveRequest $leaveRequest, string $rejectedBy, string $reason): LeaveRequest
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'leave_request_reject',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($leaveRequest, $rejectedBy, $reason, $correlationId): LeaveRequest {
            if ($leaveRequest->status !== 'pending') {
                throw new \InvalidArgumentException('Leave request is not in pending status');
            }

            $old = $leaveRequest->toArray();

            $leaveRequest->update([
                'status' => 'rejected',
                'rejected_by' => $rejectedBy,
                'rejection_reason' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->$this->logger->info('Leave request rejected', [
                'id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'correlation_id' => $correlationId,
                'rejected_by' => $rejectedBy,
                'reason' => $reason,
            ]);

            $this->audit->log(
                'leave_request_rejected',
                LeaveRequest::class,
                $leaveRequest->id,
                $old,
                $leaveRequest->fresh()->toArray(),
                $correlationId
            );

            return $leaveRequest->fresh();
        });
    }

    /**
     * Отменить запрос на отпуск (до одобрения)
     */
    public function cancelLeaveRequest(LeaveRequest $leaveRequest): LeaveRequest
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'leave_request_cancel',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($leaveRequest, $correlationId): LeaveRequest {
            if (! in_array($leaveRequest->status, ['pending', 'approved'], true)) {
                throw new \InvalidArgumentException('Leave request cannot be cancelled in current status');
            }

            $old = $leaveRequest->toArray();

            // Если был одобрен - вернуть дни в баланс
            if ($leaveRequest->status === 'approved') {
                $this->updateLeaveBalance($leaveRequest->employee_id, $leaveRequest->days);
            }

            $leaveRequest->update([
                'status' => 'cancelled',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->$this->logger->info('Leave request cancelled', [
                'id' => $leaveRequest->id,
                'employee_id' => $leaveRequest->employee_id,
                'correlation_id' => $correlationId,
                'previous_status' => $old['status'],
            ]);

            $this->audit->log(
                'leave_request_cancelled',
                LeaveRequest::class,
                $leaveRequest->id,
                $old,
                $leaveRequest->fresh()->toArray(),
                $correlationId
            );

            return $leaveRequest->fresh();
        });
    }

    /**
     * Получить запросы на отпуск сотрудника
     */
    public function getStaffLeaveRequests(int $staffId, array $filters = []): Collection
    {
        $query = LeaveRequest::where('employee_id', $staffId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить запросы на отпуск по tenant
     */
    public function getTenantLeaveRequests(int $tenantId, array $filters = []): Collection
    {
        $query = LeaveRequest::where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['staff_id']) || ! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id'] ?? $filters['staff_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить баланс отпусков сотрудника
     */
    public function getLeaveBalance(int $staffId): array
    {
        // В реальной реализации это должно быть в отдельной таблице/модели LeaveBalance
        // Для MVP используем агрегацию по одобренным отпускам
        $totalGranted = LeaveRequest::where('employee_id', $staffId)
            ->where('leave_type', 'annual')
            ->where('status', 'approved')
            ->sum('days');

        $totalUsed = LeaveRequest::where('employee_id', $staffId)
            ->where('leave_type', 'annual')
            ->whereIn('status', ['approved', 'completed'])
            ->where('end_date', '<=', CarbonImmutable::now())
            ->sum('days');

        $standardAllowance = 28; // 28 дней стандартный отпуск в РФ

        return [
            'total_granted' => $totalGranted,
            'total_used' => $totalUsed,
            'available' => $standardAllowance - $totalUsed,
            'standard_allowance' => $standardAllowance,
        ];
    }

    /**
     * Получить статистику отпусков
     */
    public function getLeaveStats(int $staffId, string $year): array
    {
        $requests = LeaveRequest::where('employee_id', $staffId)
            ->whereYear('start_date', $year)
            ->get();

        return [
            'total_requests' => $requests->count(),
            'approved' => $requests->where('status', 'approved')->count(),
            'rejected' => $requests->where('status', 'rejected')->count(),
            'cancelled' => $requests->where('status', 'cancelled')->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'total_days_taken' => $requests->whereIn('status', ['approved', 'completed'])->sum('days'),
        ];
    }

    /**
     * Обновить баланс отпусков
     */
    private function updateLeaveBalance(int $staffId, int $days): void
    {
        // В реальной реализации обновляем таблицу leave_balances
        // Для MVP это заглушка - реальный баланс считается в getLeaveBalance()
        $this->logger->$this->logger->info('Leave balance updated', [
            'employee_id' => $staffId,
            'days' => $days,
        ]);
    }

    /**
     * Рассчитать количество дней отпуска
     */
    private function calculateLeaveDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        return (int) $start->diffInDays($end) + 1; // +1 т.к. включаем оба дня
    }

    /**
     * Проверить перекрытие с другими отпусками
     */
    private function checkLeaveOverlap(int $staffId, string $startDate, string $endDate, ?int $excludeRequestId = null): bool
    {
        $query = LeaveRequest::where('employee_id', $staffId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            });

        if ($excludeRequestId !== null) {
            $query->where('id', '!=', $excludeRequestId);
        }

        return $query->exists();
    }
}
