<?php

declare(strict_types=1);

namespace App\Domains\ToysKids\Http\Controllers;

use App\Domains\ToysKids\Models\ToyProduct;
use App\Domains\ToysKids\Models\ToyOrder;
use App\Domains\ToysKids\Services\ToyService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Игрушки и товары для детей — КАНОН 2026.
 */
final class ToyProductController
{
    public function __construct(
        private readonly ToyService $toyService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $tenantId = auth()->user()?->tenant_id ?? 0;

            $products = ToyProduct::where('tenant_id', $tenantId)
                ->when($request->input('age_min'),  fn ($q, $v) => $q->where('age_min', '>=', (int) $v))
                ->when($request->input('age_max'),  fn ($q, $v) => $q->where('age_max', '<=', (int) $v))
                ->when($request->input('gender'),   fn ($q, $v) => $q->where('gender', $v))
                ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
                ->when($request->input('search'),   fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->orderByDesc('rating')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $products, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ToysKids: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $product = ToyProduct::findOrFail($id);
            return response()->json(['success' => true, 'data' => $product, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Товар не найден', 'correlation_id' => $correlationId], 404);
        }
    }

    public function wishlist(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $validated = $request->validate(['product_id' => 'required|integer|exists:toy_products,id']);
            $key = 'wishlist:user:' . auth()->id();
            $wishlist = \Illuminate\Support\Facades\$this->cache->get($key, []);
            if (!in_array($validated['product_id'], $wishlist, true)) {
                $wishlist[] = $validated['product_id'];
                \Illuminate\Support\Facades\$this->cache->put($key, $wishlist, 86400);
            }
            return response()->json(['success' => true, 'data' => $wishlist, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка вишлиста', 'correlation_id' => $correlationId], 500);
        }
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $userId = auth()->id();

            $fraudResult = $this->fraudControlService->check(
                userId: $userId,
                operationType: 'toy_order',
                amount: (int) $request->input('total_kopecks', 0),
                correlationId: $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
            }

            $validated = $request->validate([
                'product_id'       => 'required|integer|exists:toy_products,id',
                'quantity'         => 'required|integer|min:1|max:50',
                'delivery_address' => 'required|string',
                'gift_wrapping'    => 'boolean',
                'gift_message'     => 'nullable|string|max:200',
            ]);

            $order = $this->db->transaction(function () use ($validated, $userId, $correlationId): ToyOrder {
                $product = ToyProduct::findOrFail($validated['product_id']);
                $order   = ToyOrder::create([
                    'uuid'             => Str::uuid(),
                    'tenant_id'        => auth()->user()?->tenant_id ?? 0,
                    'client_id'        => $userId,
                    'product_id'       => $validated['product_id'],
                    'quantity'         => $validated['quantity'],
                    'delivery_address' => $validated['delivery_address'],
                    'gift_wrapping'    => $validated['gift_wrapping'] ?? false,
                    'gift_message'     => $validated['gift_message'] ?? null,
                    'total_kopecks'    => $product->price * $validated['quantity'],
                    'status'           => 'pending',
                    'correlation_id'   => $correlationId,
                ]);

                $this->log->channel('audit')->info('ToysKids: Order created', [
                    'order_id' => $order->id, 'user_id' => $userId, 'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            $this->log->channel('audit')->error('ToysKids: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
        }
    }

    public function myOrders(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $orders = ToyOrder::where('client_id', auth()->id())
                ->with('product')
                ->orderByDesc('created_at')
                ->paginate(20);
            return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
        }
    }
}
