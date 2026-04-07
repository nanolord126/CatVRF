<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class CourseController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $courses = Course::where('is_published', true)
                    ->with(['instructorEarnings', 'reviews'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $courses,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to list courses', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to list courses',
                ], 500);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $course = Course::with(['lessons', 'reviews', 'instructorEarnings'])
                    ->findOrFail($id);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $course,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to show course', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Course not found',
                ], 404);
            }
        }

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
                $this->authorize('create', Course::class);

                $validated = $request->validate([
                    'title' => 'required|string|max:255',
                    'description' => 'required|string',
                    'category' => 'required|string',
                    'level' => 'required|in:beginner,intermediate,advanced,expert',
                    'price' => 'required|integer|min:0',
                    'duration_hours' => 'required|integer|min:1',
                    'thumbnail_url' => 'nullable|url',
                ]);

                $correlationId = Str::uuid()->toString();

                $course = Course::create([
                    'tenant_id' => tenant()->id,
                    'instructor_id' => $request->user()?->id,
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'category' => $validated['category'],
                    'level' => $validated['level'],
                    'price' => $validated['price'],
                    'duration_hours' => $validated['duration_hours'],
                    'thumbnail_url' => $validated['thumbnail_url'] ?? null,
                    'status' => 'draft',
                    'is_published' => false,
                    'correlation_id' => $correlationId,
                ]);

                $this->logger->info('Course created', [
                    'course_id' => $course->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $course,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to create course', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to create course',
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
                $course = Course::findOrFail($id);
                $this->authorize('update', $course);

                $validated = $request->validate([
                    'title' => 'sometimes|string|max:255',
                    'description' => 'sometimes|string',
                    'status' => 'sometimes|in:draft,published,archived',
                    'is_published' => 'sometimes|boolean',
                    'price' => 'sometimes|integer|min:0',
                ]);

                $correlationId = Str::uuid()->toString();
                $course->update($validated + ['correlation_id' => $correlationId]);

                $this->logger->info('Course updated', [
                    'course_id' => $course->id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $course,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to update course', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to update course',
                ], 500);
            }
        }

        public function delete(int $id): JsonResponse
        {
            try {
                $course = Course::findOrFail($id);
                $this->authorize('delete', $course);

                $correlationId = Str::uuid()->toString();
                $course->delete();

                $this->logger->info('Course deleted', [
                    'course_id' => $id,
                    'correlation_id' => $correlationId,
                ]);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Course deleted',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to delete course', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete course',
                ], 500);
            }
        }

        public function categories(): JsonResponse
        {
            try {
                $categories = CourseCategory::where('is_active', true)->get();

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $categories,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch categories', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to fetch categories',
                ], 500);
            }
        }

        public function analytics(int $id): JsonResponse
        {
            try {
                $course = Course::findOrFail($id);
                $this->authorize('update', $course);

                $analytics = [
                    'total_students' => $course->enrollments()->count(),
                    'completed_students' => $course->enrollments()->where('status', 'completed')->count(),
                    'total_revenue' => $course->enrollments()->sum('course_price'),
                    'platform_commission' => $course->enrollments()->sum('commission_price'),
                    'average_rating' => $course->rating,
                    'total_reviews' => $course->reviews()->count(),
                ];

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $analytics,
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to fetch analytics', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
                ]);
                return new \Illuminate\Http\JsonResponse([
                    'success' => false,
                    'message' => 'Failed to fetch analytics',
                ], 500);
            }
        }
}
