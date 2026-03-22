<?php declare(strict_types=1);

namespace App\Domains\FashionRetail\Http\Controllers;

use App\Domains\FashionRetail\Models\FashionRetailOrder;
use App\Domains\FashionRetail\Services\OrderService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FashionRetailOrderController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $orders = FashionRetailOrder::where('user_id', auth()->id())
                ->orWhere('user_id', request()->user()?->id)
                ->with('shop', 'user')
                ->paginate(20);

            $correlationId = Str::uuid()->toString();
            Log::channel('audit')->info('FashionRetail orders listed', [
                'count' => $orders->count(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail order listing failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = FashionRetailOrder::with('shop', 'user', 'returns')->findOrFail($id);

            if ($order->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'correlation_id' => Str::uuid(),
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => Str::uuid(),
            ], 404);
        }
    }

    public function store(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $order = DB::transaction(function () use ($correlationId) {
                return FashionRetailOrder::create([
                    'uuid' => Str::uuid(),
                    'tenant_id' => tenant('id'),
                    'business_group_id' => filament()->getTenant()->business_group_id,
                    'user_id' => auth()->id(),
                    'shop_id' => request('shop_id'),
                    'order_number' => 'FRO-' . date('YmdHis') . Str::random(4),
                    'items' => request('items', []),
                    'total_amount' => request('total_amount'),
                    'discount_amount' => request('discount_amount', 0),
                    'commission_amount' => request('commission_amount', 0),
                    'delivery_fee' => request('delivery_fee', 0),
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'delivery_address' => request('delivery_address'),
                    'delivery_method' => request('delivery_method', 'standard'),
                    'notes' => request('notes'),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail order created', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function updateStatus(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $order = FashionRetailOrder::findOrFail($id);

            DB::transaction(function () use ($order, $correlationId) {
                $order->update([
                    'status' => request('status'),
                    'correlation_id' => $correlationId,
                ]);
            });

            Log::channel('audit')->info('FashionRetail order status updated', [
                'order_id' => $id,
                'status' => request('status'),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $e) {
            $correlationId = Str::uuid()->toString();
            Log::error('FashionRetail order status update failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
