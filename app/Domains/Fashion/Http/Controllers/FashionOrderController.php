<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Models\FashionOrder;
use App\Domains\Fashion\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionOrderController
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {}

    public function myOrders(): JsonResponse
    {
        try {
            $orders = FashionOrder::where('customer_id', auth()->id())
                ->with('store')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function store(): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid();

            $order = $this->orderService->createOrder(
                tenant('id'),
                request('store_id'),
                auth()->id(),
                request('items', []),
                request('subtotal'),
                request('shipping_cost'),
                request('shipping_address'),
                $correlationId,
            );

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 400);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = FashionOrder::with('store')->findOrFail($id);
            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Order not found', 'correlation_id' => Str::uuid()], 404);
        }
    }

    public function update(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $order = FashionOrder::findOrFail($id);
            $correlationId = Str::uuid();

            DB::transaction(function () use ($order, $correlationId) {
                $order->update([...request()->except(['id', 'tenant_id', 'business_group_id', 'correlation_id']), 'correlation_id' => $correlationId]);
                Log::channel('audit')->info('Fashion order updated', ['order_id' => $id, 'correlation_id' => $correlationId]);
            });

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        try {
            $order = FashionOrder::findOrFail($id);
            $correlationId = Str::uuid();

            $this->orderService->cancelOrder($order, request('reason'), $correlationId);

            return response()->json(['success' => true, 'data' => null, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function history(int $id): JsonResponse
    {
        try {
            $order = FashionOrder::findOrFail($id);
            return response()->json(['success' => true, 'data' => $order->fresh(), 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $orders = FashionOrder::with('store', 'customer')->paginate(50);
            return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => Str::uuid()]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function updateStatus(int $id): JsonResponse
    {
        try {
            $order = FashionOrder::findOrFail($id);
            $correlationId = Str::uuid();

            $this->orderService->updateOrderStatus($order, request('status'), $correlationId);

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }

    public function analytics(): JsonResponse
    {
        try {
            $totalOrders = FashionOrder::count();
            $deliveredOrders = FashionOrder::where('status', 'delivered')->count();
            $totalRevenue = FashionOrder::sum('total_amount');
            $avgOrderValue = FashionOrder::avg('total_amount');

            return response()->json([
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
            return response()->json(['success' => false, 'message' => $e->getMessage(), 'correlation_id' => Str::uuid()], 500);
        }
    }
}
