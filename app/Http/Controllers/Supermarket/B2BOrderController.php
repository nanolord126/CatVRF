<?php

declare(strict_types=1);

namespace App\Http\Controllers\Supermarket;

use App\Domains\Supermarket\Services\B2BService;
use App\Domains\Supermarket\Services\SupermarketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class B2BOrderController
{
    public function __construct(
        private readonly SupermarketService $supermarketService,
    ) {}

    /**
     * Получить список B2B заказов.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:pending,paid,processing,shipped,delivered,cancelled',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = DB::table('supermarket_orders')
            ->where('user_id', $request->user()->id)
            ->where('is_b2b', true);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 20);

        $orders = $query
            ->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $total = $query->count();

        return response()->json([
            'orders' => $orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'uuid' => $order->uuid,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'delivery_cost' => $order->delivery_cost,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ];
            }),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    /**
     * Получить детали B2B заказа.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $order = DB::table('supermarket_orders')
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->where('is_b2b', true)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'id' => $order->id,
            'uuid' => $order->uuid,
            'status' => $order->status,
            'total_amount' => $order->total_amount,
            'delivery_cost' => $order->delivery_cost,
            'delivery_address' => json_decode($order->delivery_address ?? '{}', true),
            'delivery_slot' => json_decode($order->delivery_slot ?? '{}', true),
            'items' => json_decode($order->items ?? '[]', true),
            'b2b_company_id' => $order->b2b_company_id,
            'b2b_documents' => json_decode($order->b2b_documents ?? '{}', true),
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ]);
    }

    /**
     * Отменить B2B заказ.
     */
    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $order = DB::table('supermarket_orders')
            ->where('id', $orderId)
            ->where('user_id', $request->user()->id)
            ->where('is_b2b', true)
            ->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found',
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'paid'])) {
            return response()->json([
                'error' => 'Cannot cancel order',
                'message' => 'Order can only be cancelled in pending or paid status',
            ], 400);
        }

        DB::table('supermarket_orders')
            ->where('id', $orderId)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'Order cancelled successfully',
        ]);
    }
}
