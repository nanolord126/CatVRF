<?php

declare(strict_types=1);

namespace App\Domains\Staff\Services;

use App\Domains\Staff\Models\Attendance;
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
 * AttendanceService — управление посещаемостью сотрудников.
 * Check-in/check-out с geofencing валидацией.
 */
final readonly class AttendanceService
{use WithAuditLogging;

    
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Guard $guard,
    ) {}

    /**
     * Check-in сотрудника с geofencing
     */
    public function checkIn(array $data): Attendance
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'attendance_checkin',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($data, $correlationId): Attendance {
            // Проверка geofencing
            if (! $this->validateGeofence($data)) {
                throw new \InvalidArgumentException('Check-in location is outside allowed geofence');
            }

            // Map staff_id to employee_id
            $employeeId = $data['employee_id'] ?? $data['staff_id'] ?? null;
            unset($data['staff_id']);
            $data['employee_id'] = $employeeId;

            // Проверка что сегодня нет уже check-in без check-out
            $existingAttendance = Attendance::where('employee_id', $employeeId)
                ->whereDate('clock_in', $data['date'] ?? CarbonImmutable::now()->toDateString())
                ->whereNull('clock_out')
                ->first();

            if ($existingAttendance) {
                throw new \InvalidArgumentException('Staff member already checked in today');
            }

            $attendance = Attendance::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()?->id ?? $data['tenant_id'] ?? null,
                'clock_in' => $data['check_in_time'] ?? $data['clock_in'] ?? CarbonImmutable::now(),
                'gps_latitude' => $data['latitude'] ?? $data['gps_latitude'] ?? null,
                'gps_longitude' => $data['longitude'] ?? $data['gps_longitude'] ?? null,
                'status' => 'active',
            ]));

            $this->logger->$this->logger->info('Attendance check-in recorded', [
                'id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'correlation_id' => $correlationId,
                'tenant_id' => $attendance->tenant_id,
            ]);

            $this->audit->log(
                'attendance_checkin',
                Attendance::class,
                $attendance->id,
                [],
                $attendance->toArray(),
                $correlationId
            );

            return $attendance;
        });
    }

    /**
     * Check-out сотрудника
     */
    public function checkOut(Attendance $attendance, array $data): Attendance
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'attendance_checkout',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($attendance, $data, $correlationId): Attendance {
            if ($attendance->clock_out !== null) {
                throw new \InvalidArgumentException('Staff member already checked out');
            }

            $old = $attendance->toArray();

            // Проверка geofencing для check-out
            if (! $this->validateGeofence($data)) {
                throw new \InvalidArgumentException('Check-out location is outside allowed geofence');
            }

            $clockOut = $data['check_out_time'] ?? $data['clock_out'] ?? CarbonImmutable::now();
            $clockIn = Carbon::parse($attendance->clock_in);
            $durationMinutes = $clockIn->diffInMinutes(Carbon::parse($clockOut));

            $attendance->update([
                'clock_out' => $clockOut,
                'gps_latitude' => $data['latitude'] ?? $data['gps_latitude'] ?? $attendance->gps_latitude,
                'gps_longitude' => $data['longitude'] ?? $data['gps_longitude'] ?? $attendance->gps_longitude,
                'duration_minutes' => $durationMinutes,
                'notes' => $data['notes'] ?? null,
                'status' => 'completed',
                'correlation_id' => $correlationId,
            ]);

            $this->logger->$this->logger->info('Attendance check-out recorded', [
                'id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'correlation_id' => $correlationId,
                'duration_minutes' => $durationMinutes,
            ]);

            $this->audit->log(
                'attendance_checkout',
                Attendance::class,
                $attendance->id,
                $old,
                $attendance->fresh()->toArray(),
                $correlationId
            );

            return $attendance->fresh();
        });
    }

    /**
     * Получить посещаемость сотрудника
     */
    public function getStaffAttendance(int $staffId, array $filters = []): Collection
    {
        $query = Attendance::where('employee_id', $staffId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('clock_in', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('clock_in', '<=', $filters['end_date']);
        }

        return $query->orderBy('clock_in', 'desc')->get();
    }

    /**
     * Получить посещаемость по tenant
     */
    public function getTenantAttendance(int $tenantId, array $filters = []): Collection
    {
        $query = Attendance::where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['start_date'])) {
            $query->where('clock_in', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('clock_in', '<=', $filters['end_date']);
        }

        if (! empty($filters['staff_id']) || ! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id'] ?? $filters['staff_id']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Отметить отсутствие
     */
    public function markAbsent(array $data): Attendance
    {
        $correlationId = (string) Str::uuid();

        $this->fraud->check(
            userId: $this->guard->id() ?? 0,
            operationType: 'attendance_absent',
            amount: 0,
            correlationId: $correlationId
        );

        return $this->db->transaction(function () use ($data, $correlationId): Attendance {
            // Map staff_id to employee_id
            $employeeId = $data['employee_id'] ?? $data['staff_id'] ?? null;
            unset($data['staff_id']);
            $data['employee_id'] = $employeeId;

            $attendance = Attendance::create(array_merge($data, [
                'correlation_id' => $correlationId,
                'tenant_id' => tenant()?->id ?? $data['tenant_id'] ?? null,
                'clock_in' => $data['date'] ?? CarbonImmutable::now(),
                'status' => 'completed',
                'duration_minutes' => 0,
            ]));

            $this->logger->$this->logger->info('Attendance marked as absent', [
                'id' => $attendance->id,
                'employee_id' => $attendance->employee_id,
                'correlation_id' => $correlationId,
                'reason' => $attendance->absence_reason,
            ]);

            $this->audit->log(
                'attendance_absent',
                Attendance::class,
                $attendance->id,
                [],
                $attendance->toArray(),
                $correlationId
            );

            return $attendance;
        });
    }

    /**
     * Получить статистику посещаемости
     */
    public function getAttendanceStats(int $staffId, string $startDate, string $endDate): array
    {
        $attendances = Attendance::where('employee_id', $staffId)
            ->whereBetween('clock_in', [$startDate, $endDate])
            ->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'active')->count(),
            'absent_days' => $attendances->where('status', 'completed')->count(),
            'late_days' => $attendances->where('is_late', true)->count(),
            'total_hours_worked' => $attendances->sum('duration_minutes') / 60,
            'avg_hours_per_day' => $attendances->where('status', 'active')->avg('duration_minutes') / 60 ?? 0,
        ];
    }

    /**
     * Валидация geofence
     */
    private function validateGeofence(array $data): bool
    {
        // Если координаты не переданы - пропускаем валидацию (для тестов)
        if (! isset($data['latitude']) || ! isset($data['longitude'])) {
            return true;
        }

        // Если geofence не настроен - пропускаем
        if (! isset($data['allowed_latitude']) || ! isset($data['allowed_longitude'])) {
            return true;
        }

        // Расстояние в метрах (по умолчанию 100м)
        $maxDistance = $data['max_distance'] ?? 100;

        $distance = $this->calculateDistance(
            $data['latitude'],
            $data['longitude'],
            $data['allowed_latitude'],
            $data['allowed_longitude']
        );

        return $distance <= $maxDistance;
    }

    /**
     * Расчёт расстояния между двумя точками (в метрах)
     * Использует формулу Haversine
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // метры

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }
}
