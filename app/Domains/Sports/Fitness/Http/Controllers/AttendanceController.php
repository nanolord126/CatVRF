<?php declare(strict_types=1);

namespace App\Domains\Sports\Fitness\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class AttendanceController extends Controller
{

    public function __construct(private readonly AttendanceService $attendanceService, private readonly LoggerInterface $logger) {}

        public function checkIn(int $scheduleId): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $attendance = $this->attendanceService->recordCheckIn($scheduleId, $request->user()?->id, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $attendance, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                $this->logger->error('Failed to check in', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function checkOut(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $attendance = Attendance::findOrFail($id);

                $this->attendanceService->recordCheckOut($attendance, $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'correlation_id' => $correlationId]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function myAttendance(): JsonResponse
        {
            try {
                $attendance = Attendance::where('member_id', $request->user()?->id)
                    ->with(['classSchedule'])
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $attendance, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function myMetrics(): JsonResponse
        {
            try {
                $metrics = PerformanceMetric::where('member_id', $request->user()?->id)
                    ->orderByDesc('metric_date')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $metrics, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        public function recordMetric(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $request->validate([
                    'metric_date' => 'required|date',
                    'calories_burned' => 'nullable|numeric',
                    'workout_duration_minutes' => 'nullable|numeric',
                ]);

                $metric = PerformanceMetric::create([
                    'tenant_id' => tenant()?->id,
                    'member_id' => $request->user()?->id,
                    'metric_date' => $request->input('metric_date'),
                    'classes_attended' => $request->input('classes_attended', 0),
                    'calories_burned' => $request->input('calories_burned', 0),
                    'workout_duration_minutes' => $request->input('workout_duration_minutes', 0),
                    'body_weight' => $request->input('body_weight'),
                    'body_fat_percentage' => $request->input('body_fat_percentage'),
                    'muscle_mass' => $request->input('muscle_mass'),
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Metric recorded', ['metric_id' => $metric->id, 'member_id' => $request->user()?->id, 'correlation_id' => $correlationId]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $metric, 'correlation_id' => $correlationId], 201);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        public function getMemberMetrics(int $memberId): JsonResponse
        {
            try {
                $metrics = PerformanceMetric::where('member_id', $memberId)
                    ->orderByDesc('metric_date')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $metrics, 'correlation_id' => Str::uuid()]);
            } catch (Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }
}
