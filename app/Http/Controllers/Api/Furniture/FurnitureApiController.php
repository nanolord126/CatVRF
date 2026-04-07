<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Furniture;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;

final class FurnitureApiController extends Controller
{

    public function __construct(
            private readonly FurnitureDomainService $furnitureService,
            private readonly AIInteriorConstructorService $aiService,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Search and Filter Furniture Objects.
         */
        public function index(FurnitureSearchRequest $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $query = FurnitureProduct::query()
                    ->where('is_published', true)
                    ->with(['store', 'category']);
                // Filters logic - extraction from Request
                if ($request->filled('category_id')) {
                    $query->where('category_id', $request->get('category_id'));
                }
                if ($request->filled('room_type_id')) {
                    $query->whereJsonContains('recommended_room_types', $request->get('room_type_id'));
                }
                if ($request->filled('style')) {
                    $query->whereJsonContains('tags', $request->get('style'));
                }
                if ($request->filled('min_price')) {
                    $query->where('price_b2c', '>=', $request->get('min_price'));
                }
                if ($request->filled('max_price')) {
                    $query->where('price_b2c', '<=', $request->get('max_price'));
                }
                // Sorting logic
                $sort = $request->get('sort_by', 'newest');
                $query = match ($sort) {
                    'price_desc' => $query->orderBy('price_b2c', 'desc'),
                    'popularity' => $query->orderBy('stock_quantity', 'asc'), // Mocked popularity via stock
                    default => $query->latest(),
                };
                $results = $query->paginate($request->get('per_page', 15));
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $results->items(),
                    'meta' => [
                        'current_page' => $results->currentPage(),
                        'total_pages' => $results->lastPage(),
                        'total_count' => $results->total(),
                    ],
                ]);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                return $this->response->json([
                    'success' => false,
                    'error' => 'Failed to fetch catalog.',
                    'correlation_id' => $correlationId,
                    'trace_id' => $e->getCode(),
                ], 500);
            }
        }
        /**
         * AI Interior Selection Endpoint.
         */
        public function aiConstruct(Request $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? (string) Str::uuid();
            $request->validate([
                'room_type_id' => 'required|exists:furniture_room_types,id',
                'style' => 'required|string|in:scandi,loft,modern',
                'budget_kopecks' => 'required|integer|min:100000',
                'photo' => 'nullable|image|max:5120',
            ]);
            try {
                $dto = new AIInteriorRequestDto(
                    roomTypeId: (int) $request->get('room_type_id'),
                    stylePreference: $request->get('style'),
                    budgetKopecks: (int) $request->get('budget_kopecks'),
                    existingFurnitureIds: $request->get('excluded_ids', []),
                    photoPath: $request->hasFile('photo') ? $request->file('photo')->getPathname() : null,
                    correlationId: $correlationId
                );
                $result = $this->aiService->generateInteriorSetup($dto);
                // Hydrate Recommended Products
                $fullProducts = FurnitureProduct::whereIn('id', $result->recommendedProductIds)->get();
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => [
                        'analysis' => $result->styleAnalysis,
                        'strategy' => $result->layoutStrategy,
                        'estimated_cost' => $result->estimatedTotal,
                        'items' => $fullProducts,
                    ],
                ]);
            } catch (Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                return $this->response->json([
                    'success' => false,
                    'message' => 'AI Interior Calculation Failed.',
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
}
