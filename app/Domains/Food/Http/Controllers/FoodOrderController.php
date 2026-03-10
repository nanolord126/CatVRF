<?php

namespace App\Domains\Food\Http\Controllers;

use App\Domains\Food\Models\FoodOrder;
use App\Domains\Food\Services\FoodService;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * FoodOrderController - API контроллер для управления заказами (Production 2026).
 *
 * @package App\Domains\Food\Http\Controllers
 */
class FoodOrderController extends Controller
{
    use AuthorizesRequests;

    private string $correlationId;

    public function __construct(
        private FoodService $foodService
    ) {
        $this->correlationId = request()->header('X-Correlation-ID', \Str::uuid()->toString());
        $this->middleware('auth:sanctum');
    }

    /**
     * GET /api/food/orders
     * Получить список заказов с фильтрацией.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', FoodOrder::class);

            $query = FoodOrder::where('tenant_id', auth()->user()->tenant_id);

            // Фильтр по статусу
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }

            // Фильтр по ресторану
            if ($request->filled('food_id')) {
                $query->where('food_id', $request->integer('food_id'));
            }

            // Для обычных пользователей - только свои заказы
            if (!auth()->user()->hasAnyRole(['admin', 'tenant-owner', 'manager', 'restaurant-staff'])) {
                $query->where('user_id', auth()->id());
            }

            $limit = $request->integer('limit', 15);
            $orders = $query->with(['foodOrderItems.foodMenu'])
                ->orderByDesc('created_at')
                ->paginate($limit);

            Log::info('Fetched food orders list', [
                'count' => $orders->count(),
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                ],
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch food orders', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении заказов',
                'correlation_id' => $this->correlationId,
            ], 500);
        }
    }

    /**
     * GET /api/food/orders/{id}
     * Получить детали конкретного заказа.
     */
    public function show(FoodOrder $order): JsonResponse
    {
        try {
            $this->authorize('view', $order);

            Log::info('Fetched food order details', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order->load(['foodOrderItems.foodMenu', 'food']),
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch food order', [
                'order_id' => $order->id ?? 'unknown',
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Заказ не найден',
                'correlation_id' => $this->correlationId,
            ], 404);
        }
    }

    /**
     * POST /api/food/orders
     * Создать новый заказ.
     *
     * Request body:
     * {
     *   "food_id": 1,
     *   "items": [
     *     {"item_id": 1, "quantity": 2},
     *     {"item_id": 2, "quantity": 1}
     *   ],
     *   "delivery_address": "ул. Пушкина, д. 10, кв. 5",
     *   "delivery_fee": 100.00,
     *   "notes": "Без лука и чеснока"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $this->authorize('create', FoodOrder::class);

            $validated = $request->validate([
                'food_id' => 'required|integer|exists:foods,id',
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|integer|exists:food_menus,id',
                'items.*.quantity' => 'required|integer|min:1',
                'delivery_address' => 'required|string|max:255',
                'delivery_fee' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
            ]);

            $order = $this->foodService->createOrder([
                'food_id' => $validated['food_id'],
                'items' => $validated['items'],
                'delivery_address' => $validated['delivery_address'],
                'delivery_fee' => $validated['delivery_fee'],
                'notes' => $validated['notes'],
            ]);

            Log::info('Created food order', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Заказ создан успешно',
                'correlation_id' => $this->correlationId,
            ], 201);
        } catch (Throwable $e) {
            Log::error('Failed to create food order', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании заказа: ' . $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * PATCH /api/food/orders/{id}
     * Обновить заказ.
     */
    public function update(Request $request, FoodOrder $order): JsonResponse
    {
        try {
            $this->authorize('update', $order);

            $validated = $request->validate([
                'delivery_address' => 'sometimes|string|max:255',
                'notes' => 'sometimes|string|max:500',
            ]);

            $order->update($validated);

            Log::info('Updated food order', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Заказ обновлен успешно',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to update food order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении заказа',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * DELETE /api/food/orders/{id}
     * Отменить заказ.
     */
    public function destroy(Request $request, FoodOrder $order): JsonResponse
    {
        try {
            $this->authorize('cancel', $order);

            $validated = $request->validate([
                'reason' => 'required|string|max:255',
            ]);

            $this->foodService->cancelOrder($order, $validated['reason']);

            Log::info('Cancelled food order', [
                'order_id' => $order->id,
                'reason' => $validated['reason'],
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заказ отменен',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to cancel food order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене заказа',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/food/orders/{id}/confirm
     * Подтвердить заказ (для персонала ресторана).
     */
    public function confirm(FoodOrder $order): JsonResponse
    {
        try {
            $this->authorize('confirm', $order);

            $this->foodService->confirmOrder($order);

            Log::info('Confirmed food order', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order->refresh(),
                'message' => 'Заказ подтвержден',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to confirm food order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при подтверждении заказа',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/food/orders/{id}/ready
     * Пометить заказ готовым к отправке.
     */
    public function markReady(FoodOrder $order): JsonResponse
    {
        try {
            $this->authorize('markReady', $order);

            $this->foodService->markReady($order);

            Log::info('Marked food order ready', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order->refresh(),
                'message' => 'Заказ готов к отправке',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to mark food order ready', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при изменении статуса',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }

    /**
     * POST /api/food/orders/{id}/complete
     * Завершить заказ.
     */
    public function complete(FoodOrder $order): JsonResponse
    {
        try {
            $this->authorize('complete', $order);

            $this->foodService->completeOrder($order);

            Log::info('Completed food order', [
                'order_id' => $order->id,
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $order->refresh(),
                'message' => 'Заказ завершен',
                'correlation_id' => $this->correlationId,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to complete food order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при завершении заказа',
                'correlation_id' => $this->correlationId,
            ], 422);
        }
    }
}
