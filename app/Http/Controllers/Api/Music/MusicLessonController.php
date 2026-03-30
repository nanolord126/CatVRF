<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicLessonController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly MusicService $musicService
        ) {}
        public function index(): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $lessons = $this->musicService->listLessons(tenant()->id);
                return response()->json([
                    'success' => true,
                    'data' => $lessons,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to list music lessons', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить список уроков.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        public function store(MusicLessonRequest $request): JsonResponse
        {
            $correlationId = $request->input('correlation_id', (string) Str::uuid());
            try {
                $lesson = $this->musicService->createLesson(
                    $request->validated(),
                    tenant()->id,
                    $correlationId
                );
                Log::channel('audit')->info('New music lesson created via API', [
                    'lesson_id' => $lesson->id,
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => true,
                    'data' => $lesson,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to create music lesson', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при создании урока.',
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        public function show(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $lesson = $this->musicService->getLessonWithDetails($id);
                return response()->json([
                    'success' => true,
                    'data' => $lesson,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Урок не найден.',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }
        public function update(MusicLessonRequest $request, int $id): JsonResponse
        {
            $correlationId = $request->input('correlation_id', (string) Str::uuid());
            try {
                $lesson = $this->musicService->updateLesson($id, $request->validated(), $correlationId);
                Log::channel('audit')->info('Music lesson updated via API', [
                    'lesson_id' => $id,
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => true,
                    'data' => $lesson,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при обновлении урока.',
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        public function destroy(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $this->musicService->deleteLesson($id, $correlationId);
                return response()->json([
                    'success' => true,
                    'message' => 'Урок успешно удален.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при удалении урока.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
