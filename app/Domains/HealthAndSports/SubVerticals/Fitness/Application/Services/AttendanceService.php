<?php

declare(strict_types=1);

namespace Modules\Fitness\Application\Services;

use App\Traits\WithAuditLogging;
use App\Services\Audit\AuditService;
use Carbon\CarbonImmutable;
use Modules\Fitness\Domain\Entities\Attendance;
use Modules\Fitness\Domain\Entities\Booking;
use Modules\Fitness\Domain\Entities\Client;
use Modules\Fitness\Domain\Enums\AttendanceStatus;
use Modules\Fitness\Domain\Repositories\AttendanceRepositoryInterface;
use Modules\Fitness\Domain\Repositories\BookingRepositoryInterface;
use Modules\Fitness\Domain\Repositories\ClientRepositoryInterface;

final readonly class AttendanceService
{
    use WithAuditLogging;

    public function __construct(
        private AttendanceRepositoryInterface $attendanceRepository,
        private BookingRepositoryInterface $bookingRepository,
        private ClientRepositoryInterface $clientRepository,
        private readonly AuditService $audit,
    ) {}

    public function recordAttendance(
        int $bookingId,
        AttendanceStatus $status = AttendanceStatus::PRESENT,
        ?int $trainerId = null,
        ?string $notes = null,
    ): Attendance {
        $booking = $this->bookingRepository->findById($bookingId);
        if (!$booking) {
            throw new \InvalidArgumentException('Booking not found');
        }

        $existingAttendance = $this->attendanceRepository->findByBookingId($bookingId);
        if ($existingAttendance) {
            throw new \RuntimeException('Attendance already recorded for this booking');
        }

        $attendance = Attendance::create(
            tenantId: $booking->tenantId,
            bookingId: $bookingId,
            clientId: $booking->clientId,
            scheduleSlotId: $booking->scheduleSlotId,
            status: $status,
            trainerId: $trainerId,
            notes: $notes,
        );

        $saved = $this->attendanceRepository->save($attendance);

        $this->logAction('attendance_recorded', 'Attendance', $saved->id, [
            'booking_id' => $bookingId,
            'client_id' => $booking->clientId,
            'status' => $status->value,
            'trainer_id' => $trainerId,
        ], null, $booking->tenantId);

        return $saved;
    }

    public function checkOut(int $attendanceId): Attendance
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        if (!$attendance) {
            throw new \InvalidArgumentException('Attendance not found');
        }

        if ($attendance->checkOutTime !== null) {
            throw new \RuntimeException('Already checked out');
        }

        $checkedOut = $attendance->checkOut();
        return $this->attendanceRepository->save($checkedOut);
    }

    public function markLate(int $attendanceId): Attendance
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        if (!$attendance) {
            throw new \InvalidArgumentException('Attendance not found');
        }

        $late = $attendance->markLate();
        return $this->attendanceRepository->save($late);
    }

    public function markExcused(int $attendanceId, ?string $reason = null): Attendance
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        if (!$attendance) {
            throw new \InvalidArgumentException('Attendance not found');
        }

        $excused = $attendance->markExcused($reason);
        return $this->attendanceRepository->save($excused);
    }

    public function addPerformanceMetrics(int $attendanceId, array $metrics): Attendance
    {
        $attendance = $this->attendanceRepository->findById($attendanceId);
        if (!$attendance) {
            throw new \InvalidArgumentException('Attendance not found');
        }

        $updated = $attendance->addPerformanceMetrics($metrics);
        return $this->attendanceRepository->save($updated);
    }

    public function getClientAttendance(int $clientId): array
    {
        return $this->attendanceRepository->findByClientId($clientId);
    }

    public function getSlotAttendance(int $scheduleSlotId): array
    {
        return $this->attendanceRepository->findByScheduleSlotId($scheduleSlotId);
    }

    public function getAttendanceReport(
        int $tenantId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $attendances = $this->attendanceRepository->findByDateRange($tenantId, $startDate, $endDate);

        $total = count($attendances);
        $present = count(array_filter($attendances, fn (Attendance $a) => $a->status === AttendanceStatus::PRESENT));
        $late = count(array_filter($attendances, fn (Attendance $a) => $a->status === AttendanceStatus::LATE));
        $absent = count(array_filter($attendances, fn (Attendance $a) => $a->status === AttendanceStatus::ABSENT));
        $excused = count(array_filter($attendances, fn (Attendance $a) => $a->status === AttendanceStatus::EXCUSED));

        $totalDuration = array_sum(array_filter(
            array_map(fn (Attendance $a) => $a->getDuration(), $attendances),
            fn (?int $d) => $d !== null,
        ));

        return [
            'total_attendances' => $total,
            'present_count' => $present,
            'late_count' => $late,
            'absent_count' => $absent,
            'excused_count' => $excused,
            'attendance_rate' => $total > 0 ? (($present + $late) / $total) * 100 : 0,
            'total_duration_minutes' => $totalDuration,
            'average_duration_minutes' => $total > 0 ? $totalDuration / $total : 0,
        ];
    }

    public function getClientAttendanceStats(int $clientId): array
    {
        $attendances = $this->attendanceRepository->findByClientId($clientId);

        $total = count($attendances);
        $present = count(array_filter($attendances, fn (Attendance $a) => $a->status === AttendanceStatus::PRESENT));
        $late = count(array_filter($attendances, fn (Attendance $a) => $a->status === AttendanceStatus::LATE));

        return [
            'total_sessions' => $total,
            'attended_sessions' => $present,
            'late_sessions' => $late,
            'attendance_rate' => $total > 0 ? (($present + $late) / $total) * 100 : 0,
        ];
    }
}
