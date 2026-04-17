<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Food;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class OrderController extends Controller
{

    public function __construct(
            private readonly FraudControlService $fraudService,
            private readonly WalletService $walletService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
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
                return $this->db->transaction(function () use ($request, $correlationId, $tenantId) {
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
                        'user_id' => $this->guard->id(),
                        'ip_address' => $request->ip(),
                        'correlation_id' => $correlationId,
                    ]);
                    if ($fraudResult['decision'] === 'block') {
                        $this->logger->channel('fraud_alert')->warning('Food order blocked', [
                            'correlation_id' => $correlationId,
                            'amount' => $totalAmount,
                        ]);
                        return $this->response->json([
                            'success' => false,
                            'message' => 'Order creation blocked',
                            'correlation_id' => $correlationId,
                        ], 403)->send();
                    }
                    // 3. Создать заказ
                    $order = RestaurantOrder::create([
                        'tenant_id' => $tenantId,
                        'restaurant_id' => $request->integer('restaurant_id'),
                        'user_id' => $this->guard->id(),
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
                    $this->logger->channel('audit')->info('Food order created', [
                        'correlation_id' => $correlationId,
                        'order_id' => $order->id,
                        'user_id' => $this->guard->id(),
                        'total' => $totalAmount,
                        'surge_multiplier' => $surgePriceMultiplier,
                    ]);
                    return $this->response->json([
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
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Food order creation failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->db->transaction(function () use ($order, $correlationId) {
                    // Обновить статус на "cooking"
                    $order->update([
                        'status' => 'cooking',
                        'correlation_id' => $correlationId,
                    ]);
                    // KDS: отправить на кухню (в реальном приложении - вебсокет)
                    $this->logger->channel('kds')->info('Order sent to kitchen', [
                        'correlation_id' => $correlationId,
                        'order_id' => $order->id,
                        'restaurant_id' => $order->restaurant_id,
                    ]);
                    return $this->response->json([
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
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('KDS send failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
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
                return $this->db->transaction(function () use ($order, $correlationId) {
                    $order->update([
                        'status' => 'delivered',
                        'delivered_at' => now(),
                        'correlation_id' => $correlationId,
                    ]);
                    $this->logger->channel('audit')->info('Food order delivered', [
                        'correlation_id' => $correlationId,
                        'order_id' => $order->id,
                        'total' => $order->total_price,
                    ]);
                    return $this->response->json([
                        'success' => true,
                        'message' => 'Order delivered',
                        'correlation_id' => $correlationId,
                    ], 200);
                });
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('audit')->error('Order delivery failed', [
                    'correlation_id' => $correlationId,
                    'error' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Delivery failed',
                    'correlation_id' => $correlationId,
                ], 500);
            }
        }
}
