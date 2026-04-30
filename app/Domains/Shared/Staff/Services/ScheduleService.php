<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Models\Shift;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * ScheduleService — управление сменами сотрудников.
 * Создание, обновление, удаление смен с проверкой конфликтов.
 */
final readonly class ScheduleService
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Создать смену с проверкой конфликтов
     */
    public function createShift(array $data): Shift
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'shift_create',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($data, $correlationId): Shift {
            // Map staff_id to employee_id for migration compatibility
            $employeeId = $data['employee_id'] ?? $data['staff_id'] ?? null;
            unset($data['staff_id']);
            $data['employee_id'] = $employeeId;

            // Проверка конфликтов
            $hasConflict = $this->checkConflict(
                $employeeId,
                $data['start_time'],
                $data['end_time']
            );

            if ($hasConflict) {
                throw new \InvalidArgumentException('Shift conflict detected for this staff member');
            }

            $shift = Shift::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()?->id ?? $data['tenant_id'] ?? null,
                'status' => 'scheduled',
            ]));

            $this->logger->$this->logger->info('Shift created', [
                'id' => $shift->id,
                'employee_id' => $shift->employee_id,
                'correlation_id' => $correlationId,
                'tenant_id' => $shift->tenant_id,
            ]);

            $this->audit->log(
                'shift_created',
                Shift::class,
                $shift->id,
                [],
                $shift->toArray(),
                $correlationId
            );

            return $shift;
        });
    }

    /**
     * Обновить смену с проверкой конфликтов
     */
    public function updateShift(Shift $shift, array $data): Shift
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'shift_update',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($shift, $data, $correlationId): Shift {
            $old = $shift->toArray();

            // Проверка конфликтов если меняется время или сотрудник
            if (isset($data['start_time']) || isset($data['end_time']) || isset($data['staff_id']) || isset($data['employee_id'])) {
                $staffId = $data['employee_id'] ?? $data['staff_id'] ?? $shift->employee_id;
                $startTime = $data['start_time'] ?? $shift->start_time;
                $endTime = $data['end_time'] ?? $shift->end_time;

                $hasConflict = $this->checkConflict($staffId, $startTime, $endTime, $shift->id);

                if ($hasConflict) {
                    throw new \InvalidArgumentException('Shift conflict detected for this staff member');
                }
            }

            $shift->update(array_merge($data, ['correlation_id' => $correlationId]));

            $this->logger->$this->logger->info('Shift updated', [
                'id' => $shift->id,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'shift_updated',
                Shift::class,
                $shift->id,
                $old,
                $shift->fresh()->toArray(),
                $correlationId
            );

            return $shift->fresh();
        });
    }

    /**
     * Удалить смену
     */
    public function deleteShift(Shift $shift): bool
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'shift_delete',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($shift, $correlationId): bool {
            $old = $shift->toArray();
            $shift->delete();

            $this->logger->$this->logger->info('Shift deleted', [
                'id' => $old['id'] ?? null,
                'correlation_id' => $correlationId,
            ]);

            $this->audit->log(
                'shift_deleted',
                Shift::class,
                $old['id'] ?? null,
                $old,
                [],
                $correlationId
            );

            return true;
        });
    }

    /**
     * Получить смены сотрудника
     */
    public function getStaffShifts(int $staffId, array $filters = []): Collection
    {
        $query = Shift::where('employee_id', $staffId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        return $query->orderBy('start_time')->get();
    }

    /**
     * Получить смены по tenant
     */
    public function getTenantShifts(int $tenantId, array $filters = []): Collection
    {
        $query = Shift::where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        return $query->orderBy('start_time')->get();
    }

    /**
     * Отменить смену
     */
    public function cancelShift(Shift $shift, string $reason = ''): Shift
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'shift_cancel',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($shift, $reason, $correlationId): Shift {
            $old = $shift->toArray();

            $shift->update([
                'status' => 'cancelled',
                'notes' => $reason,
                'correlation_id' => $correlationId,
            ]);

            $this->logger->$this->logger->info('Shift cancelled', [
                'id' => $shift->id,
                'correlation_id' => $correlationId,
                'reason' => $reason,
            ]);

            $this->audit->log(
                'shift_cancelled',
                Shift::class,
                $shift->id,
                $old,
                $shift->fresh()->toArray(),
                $correlationId
            );

            return $shift->fresh();
        });
    }

    /**
     * Проверить конфликт смен
     */
    private function checkConflict(int $staffId, string $startTime, string $endTime, ?int $excludeShiftId = null): bool
    {
        $query = Shift::where('employee_id', $staffId)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q2) use ($startTime, $endTime) {
                        $q2->where('start_time', '<', $startTime)
                            ->where('end_time', '>', $endTime);
                    });
            });

        if ($excludeShiftId !== null) {
            $query->where('id', '!=', $excludeShiftId);
        }

        return $query->exists();
    }
}
