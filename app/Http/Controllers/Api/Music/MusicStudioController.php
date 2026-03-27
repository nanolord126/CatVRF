<?php
declare(strict_types=1);
namespace App\Http\Controllers\Api\Music;
use App\Domains\MusicAndInstruments\Music\Services\MusicService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Music\MusicStudioRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * MusicStudioController handles API operations for rehearsal studios.
 */
final class MusicStudioController extends Controller
{
    public function __construct(
        private readonly MusicService $musicService
    ) {}
    public function index(): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $studios = $this->musicService->listStudios(tenant()->id);
            return response()->json([
                'success' => true,
                'data' => $studios,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to list music studios', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список студий.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    public function store(MusicStudioRequest $request): JsonResponse
    {
        $correlationId = $request->input('correlation_id', (string) Str::uuid());
        try {
            $studio = $this->musicService->createStudio(
                $request->validated(),
                tenant()->id,
                $correlationId
            );
            Log::channel('audit')->info('New music studio created via API', [
                'studio_id' => $studio->id,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'data' => $studio,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Failed to create music studio', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании студии.',
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
    public function show(int $id): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $studio = $this->musicService->getStudioWithDetails($id);
            return response()->json([
                'success' => true,
                'data' => $studio,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Студия не найдена.',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }
    public function update(MusicStudioRequest $request, int $id): JsonResponse
    {
        $correlationId = $request->input('correlation_id', (string) Str::uuid());
        try {
            $studio = $this->musicService->updateStudio($id, $request->validated(), $correlationId);
            Log::channel('audit')->info('Music studio updated via API', [
                'studio_id' => $id,
                'correlation_id' => $correlationId,
            ]);
            return response()->json([
                'success' => true,
                'data' => $studio,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении студии.',
                'correlation_id' => $correlationId,
            ], 422);
        }
    }
    public function destroy(int $id): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        try {
            $this->musicService->deleteStudio($id, $correlationId);
            return response()->json([
                'success' => true,
                'message' => 'Студия успешно удалена.',
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении студии.',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
