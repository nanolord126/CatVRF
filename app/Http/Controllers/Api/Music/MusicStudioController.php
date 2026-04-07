<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Music;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class MusicStudioController extends Controller
{

    public function __construct(
            private readonly MusicService $musicService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {}
        public function index(): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $studios = $this->musicService->listStudios(tenant()->id);
                return $this->response->json([
                    'success' => true,
                    'data' => $studios,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to list music studios', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
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
                $this->logger->channel('audit')->info('New music studio created via API', [
                    'studio_id' => $studio->id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $studio,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to create music studio', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
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
                return $this->response->json([
                    'success' => true,
                    'data' => $studio,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
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
                $this->logger->channel('audit')->info('Music studio updated via API', [
                    'studio_id' => $id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $studio,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
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
                return $this->response->json([
                    'success' => true,
                    'message' => 'Студия успешно удалена.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return $this->response->json([
                    'success' => false,
                    'message' => 'Ошибка при удалении студии.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
