<?php declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Fashion;
use App\Domains\Fashion\Models\FashionB2BOrder;
use App\Domains\Fashion\Models\FashionProduct;
use App\Domains\Fashion\Services\FashionService;
use App\Http\Controllers\Api\V1\BaseApiV1Controller;
use App\Services\FraudControlService;
use App\Services\RateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * FashionB2BController
 * 
 * API для оптовых покупателей (B2B/ИНН).
 * Реализует канон 2026: оптовые цены, проверка ИНН, correlation_id.
 */
class FashionB2BController extends BaseApiV1Controller
{
    public function __construct(
        private readonly FashionService $fashionService,
        private readonly RateLimiterService $rateLimiter
    ) {}
    /**
     * @OA\Get(
     *     path="/api/v1/fashion/b2b/catalog",
     *     summary="Оптовый каталог товаров (B2B)",
     *     tags={"Fashion B2B"},
     *     @OA\Parameter(name="inn", in="query", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="B2B catalog successfully fetched")
     * )
     */
    public function catalog(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $request->validate([
            'inn' => 'required|string|length:10',
        ]);
        try {
            $this->rateLimiter->check($request->ip(), 'fashion_b2b_browse');
            // B2B каталог: показываем price_b2b
            $products = FashionProduct::query()
                ->where('is_active', true)
                ->where('price_b2b', '>', 0)
                ->select(['id', 'name', 'price_b2b', 'quantity', 'store_id'])
                ->paginate(50);
            Log::channel('audit')->info('B2B catalog browsed', [
                'inn' => $request->get('inn'),
                'correlation_id' => $correlationId,
                'count' => $products->count(),
            ]);
            return response()->json([
                'success' => true,
                'data' => $products,
                'correlation_id' => $correlationId
            ]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('B2B catalog error', ['msg' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['error' => 'B2B access error', 'correlation_id' => $correlationId], 403);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/v1/fashion/b2b/order",
     *     summary="Создание оптового заказа (B2B)",
     *     tags={"Fashion B2B"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="inn", type="string"),
     *         @OA\Property(property="items", type="array", @OA\Items(
     *             @OA\Property(property="product_id", type="integer"),
     *             @OA\Property(property="quantity", type="integer")
     *         ))
     *     )),
     *     @OA\Response(response=201, description="B2B Order created")
     * )
     */
    public function createOrder(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $request->validate([
            'inn' => 'required|string|length:10',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:fashion_products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        try {
            FraudControlService::check(['action' => 'b2b_order_create', 'inn' => $request->get('inn')]);
            $order = $this->fashionService->createB2BOrder(
                $request->all(),
                $correlationId
            );
            Log::channel('audit')->info('B2B order created', [
                'order_id' => $order->id,
                'inn' => $request->get('inn'),
                'correlation_id' => $correlationId
            ]);
            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'status' => 'pending_payment',
                'correlation_id' => $correlationId
            ], 201);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('B2B order failure', ['msg' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['error' => 'Order creation failed', 'correlation_id' => $correlationId], 400);
        }
    }
}
