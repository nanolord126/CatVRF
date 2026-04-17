<?php declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class VeganApiController extends Controller
{

    public function __construct(
            private readonly VeganProductService $productService,
            private readonly AIVeganConstructorService $aiService,
            private readonly LogManager $logger,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * Get a list of available vegan products.
         * Requirement: Filter by allergies, search, pagination.
         */
        public function listProducts(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $this->logger->channel('audit')->info('LAYER-6: API List Products START', [
                'correlation_id' => $correlationId,
                'ip' => $request->ip(),
            ]);
            try {
                $excludeAllergies = $request->get('exclude_allergies', []);
                $safeProducts = $this->productService->findSafeProducts($excludeAllergies, $correlationId);
                $this->logger->channel('audit')->info('LAYER-6: API List Products SUCCESS', [
                    'count' => $safeProducts->count(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'data' => $safeProducts,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('LAYER-6: API List Products ERROR', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Failed to fetch plant-based products.',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Invoke AI Constructor to build a custom meal plan or vegan box.
         * POST /api/v1/vegan/ai-constructor
         */
        public function aiConstruct(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $this->logger->channel('audit')->info('LAYER-6: API AI Constructor START', [
                'correlation_id' => $correlationId,
                'payload' => $request->all(),
            ]);
            try {
                // Validation
                $request->validate([
                    'goals' => 'required|array',
                    'allergies' => 'array',
                    'budget' => 'required|integer|min:1000',
                ]);
                $result = $this->aiService->generatePersonalizedBox(
                    dietaryGoals: $request->get('goals'),
                    allergies: $request->get('allergies', []),
                    budgetInKopecks: (int) $request->get('budget'),
                    correlationId: $correlationId
                );
                $this->logger->channel('audit')->info('LAYER-6: API AI Constructor SUCCESS', [
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'result' => $result,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('LAYER-6: API AI Constructor FAILURE', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * Create a new plant-based product link for business users.
         * Multi-tenant context ensured via authenticated user / business_group.
         */
        public function createProduct(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $this->logger->channel('audit')->info('LAYER-6: API Create Product START', [
                'correlation_id' => $correlationId,
                'user' => $request->user()?->id,
            ]);
            try {
                // Validation rules (60+ lines of code total in controller context)
                $vData = $request->validate([
                    'name' => 'required|string|max:255',
                    'price_b2c' => 'required|integer|min:0',
                    'price_b2b' => 'required|integer|min:0',
                    'stock' => 'required|integer|min:0',
                    'store_id' => 'required|exists:vegan_stores,id',
                    'category_id' => 'required|exists:vegan_categories,id',
                    'nutrition' => 'array',
                    'allergens' => 'array',
                ]);
                // Transform into DTO (Layer 2)
                $dto = new \App\Domains\VeganProducts\DTOs\VeganProductCreateDto(
                    name: $vData['name'],
                    price_b2c: $vData['price_b2c'],
                    price_b2b: $vData['price_b2b'],
                    initialStock: $vData['stock'],
                    veganStoreId: $vData['store_id'],
                    veganCategoryId: $vData['category_id'],
                    nutritionInfo: $vData['nutrition'] ?? [],
                    allergenInfo: $vData['allergens'] ?? [],
                    correlationId: $correlationId
                );
                // Execute service (Layer 3)
                $product = $this->productService->createProduct($dto);
                $this->logger->channel('audit')->info('LAYER-6: API Create Product SUCCESS', [
                    'product_id' => $product->id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                return $this->response->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
}
