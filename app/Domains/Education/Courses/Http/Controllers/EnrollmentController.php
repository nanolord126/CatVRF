<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class EnrollmentController extends Controller
{

    public function __construct(
            private readonly EnrollmentService $enrollmentService,
            private readonly ProgressTrackingService $progressService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function store(): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $validated = $request->validate([
                    'course_id' => 'required|integer|exists:courses,id',
                ]);

                $correlationId = Str::uuid()->toString();

                $enrollment = $this->enrollmentService->enrollStudent(
                    $validated['course_id'],
                    (string) $request->user()?->id,
                    $correlationId
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $enrollment,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to enroll student', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $enrollment,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to show enrollment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Enrollment not found',
                ], 404);
            }
        }

        public function myEnrollments(): JsonResponse
        {
            try {
                $enrollments = Enrollment::where('student_id', $request->user()?->id)
                    ->with(['course', 'certificate'])
                    ->paginate(10);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $enrollments,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list enrollments', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list enrollments',
                ], 500);
            }
        }

        public function update(int $id): JsonResponse
        {
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            if ($fraudResult['decision'] === 'block') {
                $this->logger->warning('Operation blocked by fraud control', [
                    'correlation_id' => $correlationId,
                    'user_id'        => $request->user()?->id,
                    'score'          => $fraudResult['score'],
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success'        => false,
                    'error'          => 'Операция заблокирована.',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            try {
                $enrollment = Enrollment::findOrFail($id);
                $this->authorize('update', $enrollment);

                $validated = $request->validate([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $enrollment,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update enrollment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                $validated = $request->validate([
                    'reason' => 'sometimes|string',
                ]);

                $correlationId = Str::uuid()->toString();
                $this->enrollmentService->dropEnrollment(
                    $enrollment,
                    $validated['reason'] ?? '',
                    $correlationId
                );

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Enrollment dropped',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to drop enrollment', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $progress,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to get enrollment progress', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
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

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $students,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list course students', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list course students',
                ], 500);
            }
        }
}
