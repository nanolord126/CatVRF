<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Http\Controllers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LessonController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly ProgressTrackingService $progressService,
            private readonly FraudControlService $fraudControlService,) {}

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

                $correlationId = Str::uuid()->toString();

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

                $correlationId = Str::uuid()->toString();
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

                $correlationId = Str::uuid()->toString();
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

                $correlationId = Str::uuid()->toString();

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
