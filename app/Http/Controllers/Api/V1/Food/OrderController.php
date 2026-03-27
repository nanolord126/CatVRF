declare(strict_types=1);
namespace App\Http\Controllers\Api\V1\Food;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Food\CreateOrderRequest;
use App\Models\Food\RestaurantOrder;
use App\Services\FraudControlService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
/**
 * Food Order API Controller.
 * Workflow: Create → Surge pricing → Payment init → KDS system → Delivery.
 *
 * Commission: 14%.
 * Surge pricing: 1.5x multiplier during peak hours.
 * KDS: Kitchen Display System - автоматическая передача на кухню.
 */
final class OrderController extends BaseApiController
{
    public function __construct(
        private readonly FraudControlService $fraudService,
        private readonly WalletService $walletService,
    ) {}
    /**
     * POST /api/v1/food/orders
     * Создать заказ в ресторане.
     *
     * @return JsonResponse
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        $tenantId = $request->getTenantId();
        try {
            return DB::transaction(function () use ($request, $correlationId, $tenantId) {
                // 1. Рассчитать сумму заказа с учётом surge pricing
                $subtotal = $request->integer('subtotal');
                $deliveryPrice = $request->integer('delivery_price', 0);
                // Surge pricing: 1.5x during peak hours (11-13, 18-21)
                $hour = now()->hour;
                $surgePriceMultiplier = ($hour >= 11 && $hour <= 13) || ($hour >= 18 && $hour <= 21) ? 1.5 : 1.0;
                $surgeDeliveryPrice = intdiv((int) ($deliveryPrice * $surgePriceMultiplier), 1);
                $totalAmount = $subtotal + $surgeDeliveryPrice;
                // 2. Fraud check
                $fraudResult = $this->fraudService->scoreOperation([
                    'type' => 'food_order',
                    'amount' => $totalAmount,
                    'user_id' => auth()->id(),
                    'ip_address' => $request->ip(),
                    'correlation_id' => $correlationId,
                ]);
                if ($fraudResult['decision'] === 'block') {
                    Log::channel('fraud_alert')->warning('Food order blocked', [
                        'correlation_id' => $correlationId,
                        'amount' => $totalAmount,
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Order creation blocked',
                        'correlation_id' => $correlationId,
                    ], 403)->send();
                }
                // 3. Создать заказ
                $order = RestaurantOrder::create([
                    'tenant_id' => $tenantId,
                    'restaurant_id' => $request->integer('restaurant_id'),
                    'user_id' => auth()->id(),
                    'subtotal' => $subtotal,
                    'delivery_price' => $surgeDeliveryPrice,
                    'surge_multiplier' => $surgePriceMultiplier,
                    'total_price' => $totalAmount,
                    'status' => 'pending',
                    'correlation_id' => $correlationId,
                    'uuid' => Str::uuid(),
                ]);
                // 4. Hold сумм в кошельке
                $this->walletService->reserveStock(
                    item_id: $order->id,
                    quantity: $totalAmount,
                    source_type: 'food_order',
                    source_id: $order->id,
                    correlation_id: $correlationId,
                );
                // 5. Логирование
                Log::channel('audit')->info('Food order created', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'total' => $totalAmount,
                    'surge_multiplier' => $surgePriceMultiplier,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $order->id,
                        'uuid' => $order->uuid,
                        'total' => $order->total_price,
                        'surge_multiplier' => $order->surge_multiplier,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Food order creation failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * POST /api/v1/food/orders/{id}/ready
     * Отправить заказ на кухню (KDS).
     */
    public function ready(RestaurantOrder $order, CreateOrderRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        try {
            return DB::transaction(function () use ($order, $correlationId) {
                // Обновить статус на "cooking"
                $order->update([
                    'status' => 'cooking',
                    'correlation_id' => $correlationId,
                ]);
                // KDS: отправить на кухню (в реальном приложении - вебсокет)
                Log::channel('kds')->info('Order sent to kitchen', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'restaurant_id' => $order->restaurant_id,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Order sent to kitchen',
                    'correlation_id' => $correlationId,
                    'data' => [
                        'id' => $order->id,
                        'status' => 'cooking',
                    ],
                ], 200);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('KDS send failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'KDS operation failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
    /**
     * POST /api/v1/food/orders/{id}/complete
     * Завершить доставку.
     */
    public function complete(RestaurantOrder $order, CreateOrderRequest $request): JsonResponse
    {
        $correlationId = $request->getCorrelationId();
        try {
            return DB::transaction(function () use ($order, $correlationId) {
                $order->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                    'correlation_id' => $correlationId,
                ]);
                Log::channel('audit')->info('Food order delivered', [
                    'correlation_id' => $correlationId,
                    'order_id' => $order->id,
                    'total' => $order->total_price,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Order delivered',
                    'correlation_id' => $correlationId,
                ], 200);
            });
        } catch (\Exception $e) {
            Log::channel('audit')->error('Order delivery failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Delivery failed',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
