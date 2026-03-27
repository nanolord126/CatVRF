<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;

use App\Domains\Sports\Fitness\Models\Attendance;
use App\Domains\Sports\Fitness\Models\PerformanceMetric;
use App\Domains\Sports\Fitness\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Throwable;

final class AttendanceController
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function checkIn(int $scheduleId): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $attendance = $this->attendanceService->recordCheckIn($scheduleId, auth()->id(), $correlationId);

            return response()->json(['success' => true, 'data' => $attendance, 'correlation_id' => $correlationId], 201);
        } catch (Throwable $e) {
            Log::channel('audit')->error('Failed to check in', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function checkOut(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $attendance = Attendance::findOrFail($id);

            $this->attendanceService->recordCheckOut($attendance, $correlationId);

            return response()->json(['success' => true, 'correlation_id' => $correlationId]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function myAttendance(): JsonResponse
    {
        try {
            $attendance = Attendance::where('member_id', auth()->id())
                ->with(['classSchedule'])
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $attendance, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function myMetrics(): JsonResponse
    {
        try {
            $metrics = PerformanceMetric::where('member_id', auth()->id())
                ->orderByDesc('metric_date')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $metrics, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function recordMetric(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            request()->validate([
                'metric_date' => 'required|date',
                'calories_burned' => 'nullable|numeric',
                'workout_duration_minutes' => 'nullable|numeric',
            ]);

            $metric = PerformanceMetric::create([
                'tenant_id' => tenant('id'),
                'member_id' => auth()->id(),
                'metric_date' => request('metric_date'),
                'classes_attended' => request('classes_attended', 0),
                'calories_burned' => request('calories_burned', 0),
                'workout_duration_minutes' => request('workout_duration_minutes', 0),
                'body_weight' => request('body_weight'),
                'body_fat_percentage' => request('body_fat_percentage'),
                'muscle_mass' => request('muscle_mass'),
                'correlation_id' => $correlationId,
            ]);

            Log::channel('audit')->info('Metric recorded', ['metric_id' => $metric->id, 'member_id' => auth()->id(), 'correlation_id' => $correlationId]);

            return response()->json(['success' => true, 'data' => $metric, 'correlation_id' => $correlationId], 201);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function getMemberMetrics(int $memberId): JsonResponse
    {
        try {
            $metrics = PerformanceMetric::where('member_id', $memberId)
                ->orderByDesc('metric_date')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $metrics, 'correlation_id' => Str::uuid()]);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
