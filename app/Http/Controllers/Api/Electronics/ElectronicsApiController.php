<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\Electronics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ElectronicsApiController extends Controller
{

    public function __construct(
            private readonly ElectronicsService $service,
            private readonly ElectronicsAIConstructorService $aiService,
            private readonly FraudControlService $fraud,
            private readonly LogManager $logger,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {}
        /**
         * List gadgets with B2C/B2B dynamic pricing logic.
         */
        public function index(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            $this->logger->channel('audit')->info('LAYER-6: Electronics API Index', [
                'user' => $request->user()?->id,
                'correlation_id' => $correlationId,
            ]);
            try {
                $isB2B = $request->boolean('is_b2b', false);
                $products = ElectronicsProduct::where('availability_status', 'in_stock')
                    ->when($isB2B, fn($q) => $q->where('is_b2b_available', true))
                    ->paginate(15);
                return $this->response->json([
                    'success' => true,
                    'correlation_id' => $correlationId,
                    'data' => $products,
                ]);
            } catch (Throwable $e) {
                $this->logger->error('LAYER-6: ERROR index electronics', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'error' => 'Unable to fetch gadgets',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
        /**
         * Create a new gadget/listing via DTO mapping.
         */
        public function store(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                // 1. Validation Logic (Inline or FormRequest)
                $validated = $request->validate([
                    'name' => 'required|string|max:255',
                    'sku' => 'required|string|unique:electronics_products,sku',
                    'brand' => 'required|string',
                    'price' => 'required|integer|min:1',
                    'electronics_store_id' => 'required|exists:electronics_stores,id',
                    'electronics_category_id' => 'required|exists:electronics_categories,id',
                    'specs' => 'nullable|array',
                ]);
                // 2. Map payload to DTO
                $dto = new ProductCreateDto(
                    name: $validated['name'],
                    sku: $validated['sku'],
                    brand: $validated['brand'],
                    price: (int) $validated['price'],
                    storeId: (int) $validated['electronics_store_id'],
                    categoryId: (int) $validated['electronics_category_id'],
                    specs: $validated['specs'] ?? [],
                    correlationId: $correlationId
                );
                // 3. Execution via Domain Service
                $product = $this->service->createProduct($dto);
                return $this->response->json([
                    'success' => true,
                    'id' => $product->id,
                    'correlation_id' => $correlationId,
                    'msg' => 'Product listed successfully'
                ], 201);
            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('LAYER-6: ERROR store electronics', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'error' => 'Data inconsistent: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 422);
            }
        }
        /**
         * AI-Powered Gadget Recommendations based on user natural language.
         */
        public function getAISuggestions(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $intent = $request->input('intent', 'I need a high-performance laptop for design');
                $dto = new AISuggestionRequestDto(
                    userId: (int) $request->user()?->id ?: 0,
                    userIntent: $intent,
                    context: ['device' => $request->header('User-Agent')],
                    correlationId: $correlationId
                );
                $result = $this->aiService->suggestCompatibility($dto);
                return $this->response->json($result);
            } catch (Throwable $e) {
                return $this->response->json([
                    'error' => 'AI Service Busy: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 503);
            }
        }
        /**
         * Process gadget purchase via Wallet + Stock lock.
         */
        public function processOrder(Request $request): JsonResponse
        {
            $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
            try {
                $validated = $request->validate([
                    'product_id' => 'required|integer',
                    'quantity' => 'required|integer|min:1',
                    'is_b2b' => 'boolean',
                ]);
                $dto = new OrderProcessDto(
                    userId: (int) $this->guard->id(),
                    productId: (int) $validated['product_id'],
                    quantity: (int) $validated['quantity'],
                    isB2B: $validated['is_b2b'] ?? false,
                    correlationId: $correlationId
                );
                $order = $this->service->processOrder($dto);
                return $this->response->json([
                    'success' => true,
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);
            } catch (Throwable $e) {
                return $this->response->json([
                    'error' => 'Order payment failed: ' . $e->getMessage(),
                    'correlation_id' => $correlationId,
                ], 400);
            }
        }
}
