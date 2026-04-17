<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Services\FashionProductCategorizationService;
use App\Domains\Fashion\Services\FashionProductFilteringService;
use App\Domains\Fashion\Services\FashionUserPatternMemoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class FashionCategorizationController
{
    public function __construct(
        private FashionProductCategorizationService $categorization,
        private FashionProductFilteringService $filtering,
        private FashionUserPatternMemoryService $patternMemory,
    ) {}

    /**
     * Автоматическая категоризация товара.
     */
    public function autoCategorize(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
            'attributes' => ['required', 'array'],
        ]);

        $result = $this->categorization->autoCategorizeProduct(
            productId: $validated['product_id'],
            attributes: $validated['attributes'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Массовая перекатегоризация товаров.
     */
    public function bulkRecategorize(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:100'],
            'product_ids.*' => ['integer', 'exists:fashion_products,id'],
        ]);

        $result = $this->categorization->bulkRecategorizeProducts(
            productIds: $validated['product_ids'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить иерархию категорий.
     */
    public function getCategoryHierarchy(Request $request): JsonResponse
    {
        $parentCategory = $request->query('parent_category');

        $hierarchy = $this->categorization->getCategoryHierarchy($parentCategory);

        return response()->json([
            'success' => true,
            'data' => $hierarchy,
        ]);
    }

    /**
     * Получить умные рекомендации категорий.
     */
    public function getCategorySuggestions(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $suggestions = $this->categorization->getSmartCategorySuggestions($userId);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Умная фильтрация товаров.
     */
    public function filterProducts(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $filters = $request->input('filters', []);
        $sortBy = $request->input('sort_by');
        $sortOrder = $request->input('sort_order', 'desc');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);

        $result = $this->filtering->filterProducts(
            userId: $userId,
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
            page: $page,
            perPage: $perPage,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить доступные фильтры.
     */
    public function getAvailableFilters(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? null;

        $filters = $this->filtering->getAvailableFilters($userId, $correlationId);

        return response()->json([
            'success' => true,
            'data' => $filters,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Сохранить предпочтения фильтров.
     */
    public function saveFilterPreferences(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'filters' => ['required', 'array'],
        ]);

        $result = $this->filtering->saveUserFilterPreferences(
            userId: $userId,
            filters: $validated['filters'],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить умные рекомендации фильтров.
     */
    public function getFilterRecommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $recommendations = $this->filtering->getSmartFilterRecommendations($userId, $correlationId);

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Записать взаимодействие пользователя.
     */
    public function recordInteraction(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:fashion_products,id'],
            'interaction_type' => ['required', 'string', 'in:view,add_to_cart,add_to_wishlist,purchase,return,review,share'],
            'context' => ['nullable', 'array'],
        ]);

        $result = $this->patternMemory->recordInteraction(
            userId: $userId,
            productId: $validated['product_id'],
            interactionType: $validated['interaction_type'],
            context: $validated['context'] ?? [],
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $result,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить паттерны памяти пользователя.
     */
    public function getMemoryPatterns(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $patterns = $this->patternMemory->getUserMemoryPatterns($userId, $correlationId);

        return response()->json([
            'success' => true,
            'data' => $patterns,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Предсказать следующее действие пользователя.
     */
    public function predictNextAction(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $predictions = $this->patternMemory->predictNextAction($userId, $correlationId);

        return response()->json([
            'success' => true,
            'data' => $predictions,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Получить рекомендации на основе памяти.
     */
    public function getMemoryRecommendations(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $limit = (int) $request->input('limit', 20);

        $recommendations = $this->patternMemory->getMemoryBasedRecommendations(
            userId: $userId,
            limit: $limit,
            correlationId: $correlationId
        );

        return response()->json([
            'success' => true,
            'data' => $recommendations,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Экспорт данных памяти (GDPR).
     */
    public function exportMemoryData(Request $request): JsonResponse
    {
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) {
            throw ValidationException::withMessages(['user' => ['Authentication required']]);
        }

        $data = $this->patternMemory->exportUserMemoryData($userId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
