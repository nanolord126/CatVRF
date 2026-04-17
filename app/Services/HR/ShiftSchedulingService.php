<?php declare(strict_types=1);

namespace App\Services\HR;

use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class ShiftSchedulingService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LogManager $logger,
        private readonly AuditService $audit,
        private readonly FraudControlService $fraud,
        private readonly Cache $cache,
    ) {}

    /**
     * Create a new shift schedule
     */
    public function createSchedule(
        int $tenantId,
        int $employeeId,
        string $shiftType,
        \DateTime $startTime,
        \DateTime $endTime,
        string $correlationId,
        array $metadata = []
    ): int {
        $this->fraud->check([
            'operation_type' => 'shift_schedule_create',
            'employee_id' => $employeeId,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use (
            $tenantId,
            $employeeId,
            $shiftType,
            $startTime,
            $endTime,
            $correlationId,
            $metadata
        ) {
            $shiftId = $this->db->table('shift_schedules')->insertGetId([
                'tenant_id' => $tenantId,
                'employee_id' => $employeeId,
                'shift_type' => $shiftType,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'scheduled',
                'correlation_id' => $correlationId,
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->audit->record(
                action: 'shift_scheduled',
                subjectType: 'shift_schedule',
                subjectId: $shiftId,
                newValues: [
                    'employee_id' => $employeeId,
                    'shift_type' => $shiftType,
                    'start_time' => $startTime->format('Y-m-d H:i:s'),
                    'end_time' => $endTime->format('Y-m-d H:i:s'),
                ],
                correlationId: $correlationId,
            );

            $this->logger->channel('audit')->info('Shift schedule created', [
                'shift_id' => $shiftId,
                'employee_id' => $employeeId,
                'shift_type' => $shiftType,
                'correlation_id' => $correlationId,
            ]);

            return $shiftId;
        });
    }

    /**
     * Get employee shifts for date range
     */
    public function getEmployeeShifts(
        int $employeeId,
        \DateTime $startDate,
        \DateTime $endDate,
        string $correlationId
    ): array {
        $cacheKey = "employee_shifts:{$employeeId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";

        return $this->cache->remember($cacheKey, 3600, function () use ($employeeId, $startDate, $endDate) {
            return $this->db->table('shift_schedules')
                ->where('employee_id', $employeeId)
                ->whereBetween('start_time', [$startDate, $endDate])
                ->orderBy('start_time')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get available employees for shift
     */
    public function getAvailableEmployees(
        int $tenantId,
        \DateTime $startTime,
        \DateTime $endTime,
        string $skill = null,
        string $correlationId
    ): array {
        $query = $this->db->table('shift_schedules')
            ->join('employees', 'shift_schedules.employee_id', '=', 'employees.id')
            ->where('shift_schedules.tenant_id', $tenantId)
            ->where('employees.is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('shift_schedules.end_time', '<=', $startTime)
                    ->orWhere('shift_schedules.start_time', '>=', $endTime);
            });

        if ($skill) {
            $query->whereJsonContains('employees.skills', $skill);
        }

        return $query->select('employees.*')
            ->distinct()
            ->get()
            ->toArray();
    }

    /**
     * Update shift status
     */
    public function updateShiftStatus(
        int $shiftId,
        string $status,
        string $correlationId
    ): bool {
        $this->fraud->check([
            'operation_type' => 'shift_status_update',
            'shift_id' => $shiftId,
            'status' => $status,
            'correlation_id' => $correlationId,
        ]);

        return $this->db->transaction(function () use ($shiftId, $status, $correlationId) {
            $updated = $this->db->table('shift_schedules')
                ->where('id', $shiftId)
                ->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);

            if ($updated) {
                $this->audit->record(
                    action: 'shift_status_updated',
                    subjectType: 'shift_schedule',
                    subjectId: $shiftId,
                    newValues: ['status' => $status],
                    correlationId: $correlationId,
                );
            }

            return $updated > 0;
        });
    }

    /**
     * Auto-generate weekly schedule for employees
     */
    public function generateWeeklySchedule(
        int $tenantId,
        array $employeeIds,
        \DateTime $weekStart,
        string $correlationId
    ): array {
        $this->fraud->check([
            'operation_type' => 'weekly_schedule_generate',
            'tenant_id' => $tenantId,
            'employee_count' => count($employeeIds),
            'correlation_id' => $correlationId,
        ]);

        $generatedShifts = [];

        foreach ($employeeIds as $employeeId) {
            $employee = $this->db->table('employees')
                ->where('id', $employeeId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$employee || !$employee->is_active) {
                continue;
            }

            for ($day = 0; $day < 7; $day++) {
                $date = clone $weekStart;
                $date->modify("+$day days");

                if (in_array($date->format('N'), json_decode($employee->working_days) ?? [1, 2, 3, 4, 5])) {
                    $startTime = clone $date;
                    $startTime->setTime(9, 0, 0);

                    $endTime = clone $date;
                    $endTime->setTime(18, 0, 0);

                    $shiftId = $this->createSchedule(
                        $tenantId,
                        $employeeId,
                        $employee->default_shift_type ?? 'regular',
                        $startTime,
                        $endTime,
                        $correlationId,
                        ['auto_generated' => true]
                    );

                    $generatedShifts[] = $shiftId;
                }
            }
        }

        $this->logger->channel('audit')->info('Weekly schedule generated', [
            'tenant_id' => $tenantId,
            'shifts_count' => count($generatedShifts),
            'correlation_id' => $correlationId,
        ]);

        return $generatedShifts;
    }
}
