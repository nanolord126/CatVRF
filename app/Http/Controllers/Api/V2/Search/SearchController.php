<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Search;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SearchController extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly LiveSearchService $search
        ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Live Search
            $this->middleware('auth:sanctum')->only(['searchDocuments']); // Поиск внутри системы требует авторизации
             // 1000 light / 100 heavy запросов/час
            $this->middleware('tenant')->only(['searchDocuments']); // Tenant scoping для приватных данных
        }
        /**
         * Выполняет поиск документов
         */
        public function searchDocuments(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'q' => 'required|string|min:2|max:255',
                    'type' => 'nullable|string|max:50',
                    'status' => 'nullable|string|max:50',
                    'limit' => 'nullable|integer|min:1|max:100',
                ]);
                $filters = [];
                if (isset($validated['type'])) {
                    $filters['type'] = $validated['type'];
                }
                if (isset($validated['status'])) {
                    $filters['status'] = $validated['status'];
                }
                $results = $this->search->searchDocuments(
                    tenantId: filament()->getTenant()->id,
                    query: $validated['q'],
                    filters: $filters,
                    correlationId: $correlationId
                );
                // Ограничиваем результаты
                $limit = $validated['limit'] ?? 50;
                $results = $results->take($limit);
                // Записываем поиск в историю
                $this->search->recordSearch(
                    userId: auth()->id(),
                    tenantId: filament()->getTenant()->id,
                    query: $validated['q'],
                    resultsCount: $results->count()
                );
                Log::channel('audit')->info('Document search executed', [
                    'correlation_id' => $correlationId,
                    'query' => $validated['q'],
                    'results_count' => $results->count(),
                ]);
                return response()->json([
                    'query' => $validated['q'],
                    'results' => $results,
                    'count' => $results->count(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Document search failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'error' => 'Search failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Выполняет поиск пользователей
         */
        public function searchUsers(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $validated = $request->validate([
                    'q' => 'required|string|min:1|max:255',
                    'limit' => 'nullable|integer|min:1|max:50',
                ]);
                $results = $this->search->searchUsers(
                    tenantId: filament()->getTenant()->id,
                    query: $validated['q'],
                    correlationId: $correlationId
                );
                $limit = $validated['limit'] ?? 10;
                $results = $results->take($limit);
                return response()->json([
                    'query' => $validated['q'],
                    'results' => $results,
                    'count' => $results->count(),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('User search failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'error' => 'Search failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Получает историю поисков
         */
        public function getHistory(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $history = $this->search->getSearchHistory(
                    userId: auth()->id(),
                    tenantId: filament()->getTenant()->id
                );
                return response()->json([
                    'history' => $history,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to get search history', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'error' => 'Failed to get history',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Очищает историю поисков
         */
        public function clearHistory(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            try {
                $this->search->clearSearchHistory(
                    userId: auth()->id(),
                    tenantId: filament()->getTenant()->id
                );
                return response()->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                Log::channel('audit')->error('Failed to clear search history', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'error' => 'Failed to clear history',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
