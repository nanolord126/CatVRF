<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Services;

use Illuminate\Support\Facades\Log;
use App\Services\FraudControlService;

use App\Domains\Sports\Fitness\Events\AttendanceRecorded;
use App\Domains\Sports\Fitness\Models\Attendance;
use App\Domains\Sports\Fitness\Models\ClassSchedule;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class AttendanceService
{
    public function recordCheckIn(int $classScheduleId, int $memberId, string $correlationId): Attendance
    {


        try {
            $schedule = ClassSchedule::findOrFail($classScheduleId);

            $attendance = DB::transaction(function () use ($schedule, $memberId, $correlationId) {
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

                Log::channel('audit')->info('Member checked in', [
                    'attendance_id' => $attendance->id,
                    'class_schedule_id' => $classScheduleId,
                    'member_id' => $memberId,
                    'correlation_id' => $correlationId,
                ]);

                return $attendance;
            });

            return $attendance;
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to record check-in', [
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
            DB::transaction(function () use ($attendance, $correlationId) {
                $checkedOutAt = now();
                $durationMinutes = $checkedOutAt->diffInMinutes($attendance->checked_in_at);

                $attendance->update([
                    'checked_out_at' => $checkedOutAt,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'checked_out',
                    'correlation_id' => $correlationId,
                ]);

                Log::channel('audit')->info('Member checked out', [
                    'attendance_id' => $attendance->id,
                    'duration_minutes' => $durationMinutes,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to record check-out', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }
}
