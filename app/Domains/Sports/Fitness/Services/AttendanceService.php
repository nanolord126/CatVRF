<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Services;



use Illuminate\Contracts\Auth\Guard;
use Psr\Log\LoggerInterface;
final readonly class AttendanceService
{
    public function __construct(
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger, private readonly Guard $guard) {}


    public function recordCheckIn(int $classScheduleId, int $memberId, string $correlationId): Attendance
        {

            try {
                $schedule = ClassSchedule::findOrFail($classScheduleId);

                $attendance = $this->db->transaction(function () use ($schedule, $memberId, $correlationId) {
                    $attendance = Attendance::create([
                        'tenant_id' => $schedule->tenant_id,
                        'class_schedule_id' => $schedule->id,
                        'member_id' => $memberId,
                        'checked_in_at' => now(),
                        'status' => 'checked_in',
                        'correlation_id' => $correlationId,
                    ]);

                    $schedule->increment('current_participants');

                    AttendanceRecorded::dispatch($attendance, $correlationId);

                    $this->logger->info('Member checked in', [
                        'attendance_id' => $attendance->id,
                        'class_schedule_id' => $classScheduleId,
                        'member_id' => $memberId,
                        'correlation_id' => $correlationId,
                    ]);

                    return $attendance;
                });

                return $attendance;
            } catch (Throwable $e) {
                $this->logger->error('Failed to record check-in', [
                    'class_schedule_id' => $classScheduleId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        public function recordCheckOut(Attendance $attendance, string $correlationId): void
        {

            try {
                $this->fraud->check(userId: $this->guard->id() ?? 0, operationType: 'mutation', amount: 0, correlationId: $correlationId ?? '');
                $this->db->transaction(function () use ($attendance, $correlationId) {
                    $checkedOutAt = now();
                    $durationMinutes = $checkedOutAt->diffInMinutes($attendance->checked_in_at);

                    $attendance->update([
                        'checked_out_at' => $checkedOutAt,
                        'duration_minutes' => $durationMinutes,
                        'status' => 'checked_out',
                        'correlation_id' => $correlationId,
                    ]);

                    $this->logger->info('Member checked out', [
                        'attendance_id' => $attendance->id,
                        'duration_minutes' => $durationMinutes,
                        'correlation_id' => $correlationId,
                    ]);
                });
            } catch (Throwable $e) {
                $this->logger->error('Failed to record check-out', [
                    'attendance_id' => $attendance->id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }
}
