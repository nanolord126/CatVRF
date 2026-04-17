<?php declare(strict_types=1);

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Routing\ResponseFactory;

final class PartySuppliesController extends Controller
{

    public function __construct(
            private PartySuppliesService $service,
            private AIPartyConstructor $aiConstructor,
        private readonly LogManager $logger,
        private readonly ResponseFactory $response,
    ) {
            // PRODUCTION-READY 2026 CANON: Middleware для Party/Events вертикали
            $this->middleware('auth:sanctum')->except(['index', 'show']); // Публичный доступ на чтение
             // 100 запросов/мин для Party
             // Определение режима B2C/B2B
            $this->middleware('tenant', ['except' => ['index', 'show']]); // Tenant scoping для мутаций
            // Fraud check только для мутаций (заказ, оплата, бронирование)
            $this->middleware(
                'fraud-check',
                ['only' => ['store', 'placeOrder', 'confirmPayment']]
            );
        }
        /**
         * Get categorized festive catalog.
         */
        public function index(Request $request): JsonResponse
        {
            $filters = $request->only(['category_id', 'theme_id', 'is_b2b']);
            $catalog = $this->service->getCatalog($filters);
            return $this->response->json([
                'success' => true,
                'catalog' => $catalog,
                'correlation_id' => $request->header('X-Correlation-ID'),
            ]);
        }
        /**
         * Get specific festive item details.
         */
        public function show(string $uuid): JsonResponse
        {
            $product = PartyProduct::where('uuid', $uuid)->with(['store', 'theme', 'category'])->firstOrFail();
            return $this->response->json([
                'success' => true,
                'product' => $product,
            ]);
        }
        /**
         * Get seasonal themes list.
         */
        public function themes(): JsonResponse
        {
            $themes = $this->service->getActiveThemes();
            return $this->response->json([
                'success' => true,
                'themes' => $themes,
            ]);
        }
        /**
         * AI matching for gift sets or decor lists.
         */
        public function constructDecor(Request $request): JsonResponse
        {
            $request->validate([
                'budget' => 'required|integer|min:1000',
                'guests' => 'required|integer|min:1',
                'theme_id' => 'exists:party_themes,id',
            ]);
            $plan = $this->aiConstructor->buildDecorPlan($request->all());
            return $this->response->json([
                'success' => true,
                'plan' => $plan,
            ]);
        }
        /**
         * Order creation with stock and prepay check.
         */
        public function storeOrder(Request $request): JsonResponse
        {
            $request->validate([
                'party_store_id' => 'required|exists:party_stores,id',
                'total_cents' => 'required|integer',
                'event_date' => 'required|date',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:party_products,id',
                'items.*.quantity' => 'required|integer|min:1',
            ]);
            try {
                $order = $this->service->createOrder($request->all());
                return $this->response->json([
                    'success' => true,
                    'order_uuid' => $order->uuid,
                    'prepayment_required' => $order->prepayment_cents,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Order creation failed via API', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Failed to create order. Check stock availability.',
                ], 422);
            }
        }
}
