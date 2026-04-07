<?php declare(strict_types=1);

namespace App\Domains\Food\Http\Controllers;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class RestaurantOrderController extends Controller
{

    public function __construct(
            private readonly RestaurantOrderService $orderService,
            private readonly FraudControlService $fraud, private readonly LoggerInterface $logger) {}

        public function index(): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $orders = RestaurantOrder::query()
                    ->where('tenant_id', tenant()->id)
                    ->where('client_id', $request->user()?->id ?? 0)
                    ->with(['restaurant', 'delivery', 'kds'])
                    ->paginate(15);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $orders,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function store(Request $request): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {

                $request->validate([
                    'restaurant_id' => 'required|exists:restaurants,id',
                    'items' => 'required|array',
                    'subtotal_price' => 'required|integer',
                    'delivery' => 'boolean',
                ]);

                $order = $this->orderService->createOrder([
                    'tenant_id' => tenant()->id,
                    'restaurant_id' => $request->get('restaurant_id'),
                    'client_id' => $request->user()?->id,
                    'items' => $request->get('items'),
                    'subtotal_price' => $request->get('subtotal_price'),
                    'delivery_price' => $request->boolean('delivery') ? 50000 : 0,
                    'total_price' => $request->get('subtotal_price') + ($request->boolean('delivery') ? 50000 : 0),
                    'notes' => $request->get('notes'),
                ], $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => $order,
                    'correlation_id' => $correlationId,
                ], 201);
            } catch (\Throwable $e) {
                $this->logger->error('Order creation failed', ['error' => $e->getMessage()]);
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function show(RestaurantOrder $order): JsonResponse
        {
            $this->authorize('view', $order);

            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'data' => $order->load(['restaurant', 'delivery', 'kds']),
            ]);
        }

        public function cancel(RestaurantOrder $order): JsonResponse
        {
            try {
                $this->authorize('cancel', $order);

                $order->update(['status' => 'cancelled']);

                $this->logger->info('Order cancelled', ['order_id' => $order->id]);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ отменён']);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function confirmPayment(RestaurantOrder $order): JsonResponse
        {
            try {
                $correlationId = Str::uuid()->toString();

                $this->orderService->confirmPaymentAndSendToKitchen($order, $correlationId);

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'message' => 'Заказ отправлен на кухню',
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Ошибка'], 500);
            }
        }

        public function status(RestaurantOrder $order): JsonResponse
        {
            return new \Illuminate\Http\JsonResponse([
                'success' => true,
                'status' => $order->status,
                'kds_status' => $order->kds?->status,
                'estimated_time' => $order->kds?->total_cooking_time_minutes,
            ]);
        }

        public function kdsOrders(): JsonResponse
        {
            $orders = RestaurantOrder::query()
                ->where('status', 'confirmed')
                ->with('kds')
                ->paginate(20);

            return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders]);
        }

        public function markReady(RestaurantOrder $order): JsonResponse
        {
            try {
                $order->update(['status' => 'ready', 'ready_at' => Carbon::now()]);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ готов']);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }

        public function markPicked(RestaurantOrder $order): JsonResponse
        {
            try {
                $order->update(['status' => 'delivered', 'completed_at' => Carbon::now()]);
                event(new \App\Domains\Food\Events\OrderCompleted($order, Str::uuid()->toString()));
                return new \Illuminate\Http\JsonResponse(['success' => true, 'message' => 'Заказ выдан']);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false], 500);
            }
        }
}
