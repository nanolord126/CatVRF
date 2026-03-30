<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Music;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MusicStoreController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * Create a new controller instance.
         */
        public function __construct(
            private readonly MusicService $musicService
        ) {}
        /**
         * Display a listing of the stores.
         */
        public function index(): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $stores = $this->musicService->listStores(tenant()->id);
                return response()->json([
                    'success' => true,
                    'data' => $stores,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to list music stores', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить список магазинов.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Store a newly created store in storage.
         */
        public function store(MusicStoreRequest $request): JsonResponse
        {
            $correlationId = $request->input('correlation_id', (string) Str::uuid());
            try {
                $store = $this->musicService->createStore(
                    $request->validated(),
                    tenant()->id,
                    $correlationId
                );
                Log::channel('audit')->info('New music store created via API', [
                    'store_id' => $store->id,
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => true,
                    'data' => $store,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to create music store', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при создании магазина: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Display the specified store.
         */
        public function show(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $store = $this->musicService->getStoreById($id);
                return response()->json([
                    'success' => true,
                    'data' => $store,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Магазин не найден.',
                    'correlation_id' => $correlationId,
                ], 404);
            }
        }
        /**
         * Update the specified store.
         */
        public function update(MusicStoreRequest $request, int $id): JsonResponse
        {
            $correlationId = $request->input('correlation_id', (string) Str::uuid());
            try {
                $store = $this->musicService->updateStore(
                    $id,
                    $request->validated(),
                    $correlationId
                );
                return response()->json([
                    'success' => true,
                    'data' => $store,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to update music store', [
                    'store_id' => $id,
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при обновлении магазина.',
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Remove the specified store.
         */
        public function destroy(int $id): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $this->musicService->deleteStore($id, $correlationId);
                return response()->json([
                    'success' => true,
                    'message' => 'Магазин успешно удален.',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при удалении магазина.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
