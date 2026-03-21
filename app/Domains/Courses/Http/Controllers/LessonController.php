<?php declare(strict_types=1);

namespace App\Domains\Courses\Http\Controllers;

use App\Domains\Courses\Models\Lesson;
use App\Domains\Courses\Models\Course;
use App\Domains\Courses\Services\ProgressTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class LessonController
{
    public function __construct(
        private readonly ProgressTrackingService $progressService,
    ) {}

    public function indexByCourse(int $id): JsonResponse
    {
        try {
            $lessons = Lesson::where('course_id', $id)
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $lessons,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to list lessons', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to list lessons',
            ], 500);
        }
    }

    public function store(int $courseId): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $course = Course::findOrFail($courseId);
            $this->authorize('update', $course);

            $validated = request()->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'content' => 'required|string',
                'video_url' => 'nullable|url',
                'duration_minutes' => 'required|integer|min:1',
                'sort_order' => 'sometimes|integer',
            ]);

            $correlationId = Str::uuid();

            $lesson = Lesson::create([
                'tenant_id' => tenant('id'),
                'course_id' => $courseId,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'content' => $validated['content'],
                'video_url' => $validated['video_url'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'sort_order' => $validated['sort_order'] ?? 0,
                'is_published' => false,
                'correlation_id' => $correlationId,
            ]);

            \Log::channel('audit')->info('Lesson created', [
                'lesson_id' => $lesson->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create lesson', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson',
            ], 500);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $lesson = Lesson::findOrFail($id);
            $this->authorize('update', $lesson);

            $validated = request()->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'content' => 'sometimes|string',
                'video_url' => 'sometimes|nullable|url',
                'duration_minutes' => 'sometimes|integer|min:1',
                'is_published' => 'sometimes|boolean',
            ]);

            $correlationId = Str::uuid();
            $lesson->update($validated + ['correlation_id' => $correlationId]);

            \Log::channel('audit')->info('Lesson updated', [
                'lesson_id' => $lesson->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $lesson,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update lesson', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson',
            ], 500);
        }
    }

    public function delete(int $id): JsonResponse
    {
        try {
            $lesson = Lesson::findOrFail($id);
            $this->authorize('delete', $lesson);

            $correlationId = Str::uuid();
            $lesson->delete();

            \Log::channel('audit')->info('Lesson deleted', [
                'lesson_id' => $id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to delete lesson', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson',
            ], 500);
        }
    }

    public function updateProgress(int $enrollmentId, int $lessonId): JsonResponse
    {
        try {
            $validated = request()->validate([
                'watch_time_seconds' => 'required|integer|min:0',
                'mark_complete' => 'sometimes|boolean',
            ]);

            $correlationId = Str::uuid();

            if ($validated['mark_complete'] ?? false) {
                $progress = $this->progressService->markLessonComplete(
                    $enrollmentId,
                    $lessonId,
                    $correlationId
                );
            } else {
                $progress = $this->progressService->trackLessonWatch(
                    $enrollmentId,
                    $lessonId,
                    $validated['watch_time_seconds'],
                    $correlationId
                );
            }

            return response()->json([
                'success' => true,
                'data' => $progress,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to update lesson progress', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson progress',
            ], 500);
        }
    }
}
