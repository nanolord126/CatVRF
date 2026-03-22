<?php declare(strict_types=1);

namespace App\Domains\Courses\Http\Controllers;

use App\Domains\Courses\Models\Course;
use App\Domains\Courses\Models\CourseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CourseController
{
    public function __construct(
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $courses = Course::where('is_published', true)
                ->with(['instructorEarnings', 'reviews'])
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $courses,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list courses', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $course,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to show course', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
            ], 404);
        }
    }

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
            $this->authorize('create', Course::class);

            $validated = request()->validate([
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
                'tenant_id' => tenant('id'),
                'instructor_id' => auth()->id(),
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

            \Log::channel('audit')->info('Course created', [
                'course_id' => $course->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $course,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create course', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create course',
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
            $course = Course::findOrFail($id);
            $this->authorize('update', $course);

            $validated = request()->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'status' => 'sometimes|in:draft,published,archived',
                'is_published' => 'sometimes|boolean',
                'price' => 'sometimes|integer|min:0',
            ]);

            $correlationId = Str::uuid()->toString();
            $course->update($validated + ['correlation_id' => $correlationId]);

            \Log::channel('audit')->info('Course updated', [
                'course_id' => $course->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $course,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update course', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            \Log::channel('audit')->info('Course deleted', [
                'course_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Course deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to delete course', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course',
            ], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = CourseCategory::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to fetch categories', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
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

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to fetch analytics', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics',
            ], 500);
        }
    }
}
