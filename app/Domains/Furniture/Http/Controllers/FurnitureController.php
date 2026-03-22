<?php

declare(strict_types=1);

namespace App\Domains\Furniture\Http\Controllers;

use App\Domains\Furniture\Models\FurnitureItem;
use App\Domains\Furniture\Models\FurnitureOrder;
use App\Domains\Furniture\Services\FurnitureService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Мебель и интерьер — КАНОН 2026.
 */
final class FurnitureController
{
    public function __construct(
        private readonly FurnitureService $furnitureService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $tenantId = auth()->user()?->tenant_id ?? 0;

            $items = FurnitureItem::where('tenant_id', $tenantId)
                ->when($request->input('style'),     fn ($q, $v) => $q->where('style', $v))
                ->when($request->input('material'),  fn ($q, $v) => $q->where('material', $v))
                ->when($request->input('room'),      fn ($q, $v) => $q->where('room_type', $v))
                ->when($request->input('min_price'), fn ($q, $v) => $q->where('price', '>=', (int) $v))
                ->when($request->input('max_price'), fn ($q, $v) => $q->where('price', '<=', (int) $v))
                ->orderByDesc('rating')
                ->paginate(20);

            return response()->json(['success' => true, 'data' => $items, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Furniture: index error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка загрузки', 'correlation_id' => $correlationId], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $item = FurnitureItem::findOrFail($id);
            return response()->json(['success' => true, 'data' => $item, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Товар не найден', 'correlation_id' => $correlationId], 404);
        }
    }

    public function view3D(int $id): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $item   = FurnitureItem::findOrFail($id);
            $model  = $item->model_3d_url ?? null;
            if ($model === null) {
                return response()->json(['success' => false, 'message' => '3D-модель недоступна', 'correlation_id' => $correlationId], 404);
            }
            return response()->json(['success' => true, 'data' => ['model_url' => $model, 'item' => $item], 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка 3D', 'correlation_id' => $correlationId], 500);
        }
    }

    public function order(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $userId = auth()->id();

            $fraudResult = $this->fraudControlService->check(
                userId: $userId,
                operationType: 'furniture_order',
                amount: (int) $request->input('total_kopecks', 0),
                correlationId: $correlationId,
            );
            if ($fraudResult['decision'] === 'block') {
                return response()->json(['success' => false, 'message' => 'Операция заблокирована', 'correlation_id' => $correlationId], 403);
            }

            $validated = $request->validate([
                'item_id'          => 'required|integer|exists:furniture_items,id',
                'quantity'         => 'required|integer|min:1|max:50',
                'delivery_address' => 'required|string',
                'delivery_date'    => 'required|date|after:today',
                'assembly'         => 'boolean',
                'color'            => 'nullable|string',
            ]);

            $order = DB::transaction(function () use ($validated, $userId, $correlationId): FurnitureOrder {
                $item  = FurnitureItem::findOrFail($validated['item_id']);
                $order = FurnitureOrder::create([
                    'uuid'             => Str::uuid(),
                    'tenant_id'        => auth()->user()?->tenant_id ?? 0,
                    'client_id'        => $userId,
                    'item_id'          => $validated['item_id'],
                    'quantity'         => $validated['quantity'],
                    'delivery_address' => $validated['delivery_address'],
                    'delivery_date'    => $validated['delivery_date'],
                    'assembly'         => $validated['assembly'] ?? false,
                    'color'            => $validated['color'] ?? null,
                    'total_kopecks'    => $item->price * $validated['quantity'],
                    'status'           => 'pending',
                    'correlation_id'   => $correlationId,
                ]);

                Log::channel('audit')->info('Furniture: Order created', [
                    'order_id' => $order->id, 'user_id' => $userId, 'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json(['success' => true, 'data' => $order, 'correlation_id' => $correlationId], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'errors' => $e->errors(), 'correlation_id' => $correlationId], 422);
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Furniture: order error', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            return response()->json(['success' => false, 'message' => 'Ошибка заказа', 'correlation_id' => $correlationId], 500);
        }
    }

    public function myOrders(): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        try {
            $orders = FurnitureOrder::where('client_id', auth()->id())
                ->with('item')
                ->orderByDesc('created_at')
                ->paginate(20);
            return response()->json(['success' => true, 'data' => $orders, 'correlation_id' => $correlationId]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Ошибка', 'correlation_id' => $correlationId], 500);
        }
    }
}
