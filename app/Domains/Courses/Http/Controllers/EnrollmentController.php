<?php declare(strict_types=1);

namespace App\Domains\Courses\Http\Controllers;

use App\Domains\Courses\Models\Enrollment;
use App\Domains\Courses\Models\Course;
use App\Domains\Courses\Services\EnrollmentService;
use App\Domains\Courses\Services\ProgressTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class EnrollmentController
{
    public function __construct(
        private readonly EnrollmentService $enrollmentService,
        private readonly ProgressTrackingService $progressService,
        private readonly FraudControlService $fraudControlService,) {}

    public function store(): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $validated = request()->validate([
                'course_id' => 'required|integer|exists:courses,id',
            ]);

            $correlationId = Str::uuid()->toString();

            $enrollment = $this->enrollmentService->enrollStudent(
                $validated['course_id'],
                (string) auth()->id(),
                $correlationId
            );

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to enroll student', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll student',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $enrollment = Enrollment::with(['course', 'lessonProgress'])
                ->findOrFail($id);

            $this->authorize('view', $enrollment);

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to show enrollment', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found',
            ], 404);
        }
    }

    public function myEnrollments(): JsonResponse
    {
        try {
            $enrollments = Enrollment::where('student_id', auth()->id())
                ->with(['course', 'certificate'])
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $enrollments,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list enrollments', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to list enrollments',
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        $fraudResult = $this->fraudControlService->check(
            auth()->id() ?? 0,
            'operation',
            0,
            request()->ip(),
            request()->header('X-Device-Fingerprint'),
            $correlationId,
        );

        if ($fraudResult['decision'] === 'block') {
            Log::channel('fraud_alert')->warning('Operation blocked by fraud control', [
                'correlation_id' => $correlationId,
                'user_id'        => auth()->id(),
                'score'          => $fraudResult['score'],
            ]);
            return response()->json([
                'success'        => false,
                'error'          => 'Операция заблокирована.',
                'correlation_id' => $correlationId,
            ], 403);
        }

        try {
            $enrollment = Enrollment::findOrFail($id);
            $this->authorize('update', $enrollment);

            $validated = request()->validate([
                'status' => 'sometimes|in:active,completed,dropped,paused',
            ]);

            $correlationId = Str::uuid()->toString();

            if ($validated['status'] === 'completed') {
                $enrollment = $this->enrollmentService->completeEnrollment(
                    $enrollment,
                    $correlationId
                );
            } else {
                $enrollment->update($validated);
            }

            return response()->json([
                'success' => true,
                'data' => $enrollment,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update enrollment', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update enrollment',
            ], 500);
        }
    }

    public function drop(int $id): JsonResponse
    {
        try {
            $enrollment = Enrollment::findOrFail($id);
            $this->authorize('update', $enrollment);

            $validated = request()->validate([
                'reason' => 'sometimes|string',
            ]);

            $correlationId = Str::uuid()->toString();
            $this->enrollmentService->dropEnrollment(
                $enrollment,
                $validated['reason'] ?? '',
                $correlationId
            );

            return response()->json([
                'success' => true,
                'message' => 'Enrollment dropped',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to drop enrollment', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to drop enrollment',
            ], 500);
        }
    }

    public function progress(int $enrollmentId): JsonResponse
    {
        try {
            $this->authorize('view', Enrollment::findOrFail($enrollmentId));

            $correlationId = Str::uuid()->toString();
            $progress = $this->progressService->getEnrollmentProgress(
                $enrollmentId,
                $correlationId
            );

            return response()->json([
                'success' => true,
                'data' => $progress,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to get enrollment progress', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get enrollment progress',
            ], 500);
        }
    }

    public function courseStudents(int $courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);
            $this->authorize('update', $course);

            $students = Enrollment::where('course_id', $courseId)
                ->with(['student'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $students,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list course students', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to list course students',
            ], 500);
        }
    }
}
