<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;


use Psr\Log\LoggerInterface;
use App\Http\Controllers\Controller;

final class FashionOrderController extends Controller
{

    public function __construct(private readonly OrderService $orderService,
            private readonly FraudControlService $fraud,
        private readonly \Illuminate\Database\DatabaseManager $db, private readonly LoggerInterface $logger) {}

        public function myOrders(): JsonResponse
        {
            try {
                $orders = FashionOrder::where('customer_id', $request->user()?->id)
                    ->with('store')
                    ->paginate(20);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function store(): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $order = $this->orderService->createOrder(
                    tenant()->id,
                    $request->input('store_id'),
                    $request->user()?->id,
                    $request->input('items', []),
                    $request->input('subtotal'),
                    $request->input('shipping_cost'),
                    $request->input('shipping_address'),
                    $correlationId,
                );

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
            }
        }

        public function show(int $id): JsonResponse
        {
            try {
                $order = FashionOrder::with('store')->findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => 'Order not found', 'correlation_id' => Str::uuid()], 404);
            }
        }

        public function update(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();
            $this->fraud->check(userId: $request->user()?->id ?? 0, operationType: 'operation', amount: 0, correlationId: $correlationId ?? '');

            try {
                $order = FashionOrder::findOrFail($id);

                $this->db->transaction(function () use ($order, $correlationId) {
                    $order->update([...$request->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                    $this->logger->info('Fashion order updated', ['order_id' => $id, 'correlation_id' => $correlationId]);
                });

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function cancel(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $order = FashionOrder::findOrFail($id);

                $this->orderService->cancelOrder($order, $request->input('reason'), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function history(int $id): JsonResponse
        {
            try {
                $order = FashionOrder::findOrFail($id);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order->fresh(), 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function all(): JsonResponse
        {
            try {
                $orders = FashionOrder::with('store', 'customer')->paginate(50);
                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function updateStatus(int $id): JsonResponse
        {
            $correlationId = Str::uuid()->toString();

            try {
                $order = FashionOrder::findOrFail($id);

                $this->orderService->updateOrderStatus($order, $request->input('status'), $correlationId);

                return new \Illuminate\Http\JsonResponse(['success' => true, 'data' => $order, 'correlation_id' => $correlationId]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }

        public function analytics(): JsonResponse
        {
            try {
                $totalOrders = FashionOrder::count();
                $deliveredOrders = FashionOrder::where('status', 'delivered')->count();
                $totalRevenue = FashionOrder::sum('total_amount');
                $avgOrderValue = FashionOrder::avg('total_amount');

                return new \Illuminate\Http\JsonResponse([
                    'success' => true,
                    'data' => [
                        'total_orders' => $totalOrders,
                        'delivered' => $deliveredOrders,
                        'total_revenue' => round($totalRevenue, 2),
                        'avg_order_value' => round($avgOrderValue, 2),
                    ],
                    'correlation_id' => Str::uuid(),
                ]);
            } catch (\Throwable $e) {
                return new \Illuminate\Http\JsonResponse(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
            }
        }
}
