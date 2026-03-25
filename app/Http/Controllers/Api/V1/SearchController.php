<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Services\Search\SearchService;
use App\Services\Search\ElasticsearchService;
use App\Services\Security\RateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Search",
 *     description="Global search across all verticals"
 * )
 */
final class SearchController extends BaseApiV1Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly ElasticsearchService $elasticsearchService,
        private readonly RateLimiterService $rateLimiterService,
    ) {}

    /**
     * Global search across all verticals
     *
     * @OA\Get(
     *     path="/v1/search",
     *     tags={"Search"},
     *     summary="Search across all verticals",
     *     description="Performs a global search using Elasticsearch with filters and sorting",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query",
     *         required=true,
     *         @OA\Schema(type="string", example="салон красоты")
     *     ),
     *     @OA\Parameter(
     *         name="vertical",
     *         in="query",
     *         description="Filter by vertical (beauty, food, hotels, auto, etc)",
     *         @OA\Schema(type="string", example="beauty")
     *     ),
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price in kopeks",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price in kopeks",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="geo",
     *         in="query",
     *         description="Geographic filter (lat,lng,radius_km)",
     *         @OA\Schema(type="string", example="55.7558,37.6173,5")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Sort by (relevance, rating, price_asc, price_desc, newest)",
     *         @OA\Schema(type="string", example="relevance")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Pagination page",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page (max 100)",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=150),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="results", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="string", format="uuid"),
     *                         @OA\Property(property="vertical", type="string", example="beauty"),
     *                         @OA\Property(property="type", type="string", example="salon"),
     *                         @OA\Property(property="title", type="string"),
     *                         @OA\Property(property="description", type="string"),
     *                         @OA\Property(property="rating", type="number", format="float"),
     *                         @OA\Property(property="price", type="integer"),
     *                         @OA\Property(property="image", type="string", format="url"),
     *                         @OA\Property(property="location", type="object",
     *                             @OA\Property(property="lat", type="number"),
     *                             @OA\Property(property="lng", type="number")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="correlation_id", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid search parameters"
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded (heavy searches: 100/hour, light: 1000/hour)"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
        
        try {
            $validated = $request->validate([
                'q' => 'required|string|min:2|max:255',
                'vertical' => 'nullable|string|in:beauty,food,hotels,auto,realestate,courses,medical,pet,tickets,travel,construction,electronics,furniture,sports,logistics,books,cosmetics,jewelry,gifts,medical_supplies,fresh_produce,grocery,pharmacy,healthy_food,confectionery,meat_shops,office_catering,farm_direct',
                'category' => 'nullable|string|max:100',
                'min_price' => 'nullable|integer|min:0',
                'max_price' => 'nullable|integer|min:0',
                'geo' => 'nullable|regex:/^-?\d+\.?\d*,-?\d+\.?\d*,\d+\.?\d*$/',
                'sort' => 'nullable|string|in:relevance,rating,price_asc,price_desc,newest',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $tenantId = (int) tenant('id');
            $userId = $request->user()?->id;
            
            // Rate limiting check (different for light vs heavy searches)
            $isHeavySearch = !empty($validated['geo']) || !empty($validated['category']);
            $rateLimitKey = $isHeavySearch ? 'search:heavy' : 'search:light';
            $rateLimitPassed = $this->rateLimiterService->check(
                key: $rateLimitKey . ':' . ($userId ?? $request->ip()),
                limit: $isHeavySearch ? 100 : 1000,
                window: 3600, // 1 hour
                correlationId: $correlationId,
            );

            if (!$rateLimitPassed) {
                $this->log->channel('fraud_alert')->warning('Search rate limit exceeded', [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId,
                    'query' => $validated['q'],
                    'is_heavy' => $isHeavySearch,
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Лимит поисков превышен. Попробуйте позже.',
                    'correlation_id' => $correlationId,
                ], 429);
            }

            // Execute search
            $results = $this->elasticsearchService->search(
                query: $validated['q'],
                filters: [
                    'tenant_id' => $tenantId,
                    'vertical' => $validated['vertical'] ?? null,
                    'category' => $validated['category'] ?? null,
                    'min_price' => $validated['min_price'] ?? null,
                    'max_price' => $validated['max_price'] ?? null,
                    'geo' => $validated['geo'] ?? null,
                ],
                sort: $validated['sort'] ?? 'relevance',
                page: $validated['page'] ?? 1,
                perPage: $validated['per_page'] ?? 20,
            );

            $this->log->channel('audit')->info('Search executed', [
                'correlation_id' => $correlationId,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'query' => $validated['q'],
                'results_count' => $results['total'],
                'vertical' => $validated['vertical'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $results,
                'correlation_id' => $correlationId,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                message: 'Ошибка валидации',
                statusCode: 422,
                errors: $e->errors(),
            );
        } catch (\Throwable $e) {
            $this->log->channel('error')->error('Search error', [
                'correlation_id' => $correlationId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse(
                message: 'Ошибка поиска',
                statusCode: 500,
            );
        }
    }

    /**
     * Autocomplete search suggestions
     *
     * @OA\Get(
     *     path="/v1/search/suggestions",
     *     tags={"Search"},
     *     summary="Get search suggestions",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Partial query for suggestions",
     *         required=true,
     *         @OA\Schema(type="string", minLength=1, maxLength=50)
     *     ),
     *     @OA\Parameter(
     *         name="vertical",
     *         in="query",
     *         description="Filter suggestions by vertical",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Maximum suggestions (max 10)",
     *         @OA\Schema(type="integer", default=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suggestions list",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="text", type="string"),
     *                     @OA\Property(property="vertical", type="string"),
     *                     @OA\Property(property="category", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function suggestions(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        
        try {
            $validated = $request->validate([
                'q' => 'required|string|min:1|max:50',
                'vertical' => 'nullable|string',
                'limit' => 'nullable|integer|min:1|max:10',
            ]);

            $suggestions = $this->elasticsearchService->suggestions(
                query: $validated['q'],
                vertical: $validated['vertical'],
                limit: $validated['limit'] ?? 5,
                tenantId: (int) tenant('id'),
            );

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'correlation_id' => $correlationId,
            ], 200);

        } catch (\Throwable $e) {
            $this->log->channel('error')->error('Search suggestions error', [
                'correlation_id' => $correlationId,
                'exception' => $e->getMessage(),
            ]);
            return $this->errorResponse('Ошибка получения подсказок');
        }
    }
}
