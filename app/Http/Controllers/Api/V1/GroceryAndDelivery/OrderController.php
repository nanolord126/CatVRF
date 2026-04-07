<?php declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\GroceryAndDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class OrderController extends Controller
{
    public function __construct(
            private readonly GroceryOrderService $orderService,
            private readonly LogManager $logger,
            private readonly DatabaseManager $db,
            private readonly Guard $guard,
            private readonly ResponseFactory $response,
    ) {}

        /**
         * Создать заказ
         * POST /api/v1/grocery/orders
         */
        public function store(CreateOrderRequest $request): JsonResponse
        {
            $correlationId = $request->get('correlation_id') ?? (string) Str::uuid();

            try {
                $order = $this->orderService->createOrder(
                    userId: $this->guard->id(),
                    storeId: $request->get('store_id'),
                    deliverySlotId: $request->get('delivery_slot_id'),
                    items: $request->get('items'),
                    lat: $request->get('lat'),
                    lon: $request->get('lon'),
                    correlationId: $correlationId,
                );

                return $this->response->json([
                    'success' => true,
                    'data' => new GroceryOrderResource($order),
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Order creation failed', [
                    'user_id' => $this->guard->id(),
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Получить заказ
         * GET /api/v1/grocery/orders/{id}
         */
        public function show(GroceryOrder $order): JsonResponse
        {
            $this->authorize('view', $order);

            return $this->response->json([
                'success' => true,
                'data' => new GroceryOrderResource($order),
            ]);
        }

        /**
         * Отменить заказ
         * POST /api/v1/grocery/orders/{id}/cancel
         */
        public function cancel(GroceryOrder $order): JsonResponse
        {
            $this->authorize('update', $order);
            $correlationId = (string) Str::uuid();

            try {
                $cancelled = $this->orderService->cancelOrder($order, 'user_requested', $correlationId);

                return $this->response->json([
                    'success' => true,
                    'data' => new GroceryOrderResource($cancelled),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Order cancellation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        }

        /**
         * Подтвердить заказ (оплата)
         * POST /api/v1/grocery/orders/{id}/confirm
         */
        public function confirm(GroceryOrder $order): JsonResponse
        {
            $this->authorize('update', $order);
            $correlationId = (string) Str::uuid();

            $confirmed = $this->orderService->confirmOrder($order, $correlationId);

            return $this->response->json([
                'success' => true,
                'data' => new GroceryOrderResource($confirmed),
                'correlation_id' => $correlationId,
            ]);
        }
}
