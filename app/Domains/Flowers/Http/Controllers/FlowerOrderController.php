<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Services\FlowerOrderService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class FlowerOrderController
{
    public function __construct(
        private readonly FlowerOrderService $orderService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $validated = $request->validate([
                'shop_id' => 'required|integer|exists:flower_shops,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:flower_products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'recipient_name' => 'required|string|max:255',
                'recipient_phone' => 'required|string',
                'delivery_address' => 'required|string',
                'delivery_date' => 'required|date|after:today',
                'delivery_fee' => 'numeric|min:0',
            ]);

            $order = $this->orderService->createPublicOrder(
                tenantId: filament()->getTenant()->id,
                userId: auth()->id(),
                shopId: $validated['shop_id'],
                items: $validated['items'],
                deliveryData: $validated,
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $order->load('items.product'),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_CREATED);
        } catch (\Exception $exception) {
            Log::channel('audit')->error('Order creation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function myOrders(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $orders = $this->orderService->getPublicOrders(
                tenantId: filament()->getTenant()->id,
                userId: auth()->id(),
            );

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = FlowerOrder::query()
                ->where('id', $id)
                ->with(['user', 'shop', 'items.product', 'delivery'])
                ->firstOrFail();

            $this->authorize('view', $order);

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_NOT_FOUND);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = FlowerOrder::query()->findOrFail($id);
            
            $this->authorize('update', $order);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel this order',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_UNPROCESSABLE_ENTITY);
            }

            $order = DB::transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'cancelled']);

                Log::channel('audit')->info('Flower order cancelled', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function receipt(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = FlowerOrder::query()
                ->where('id', $id)
                ->with(['items.product', 'shop'])
                ->firstOrFail();

            $this->authorize('view', $order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => $order->order_number,
                    'shop_name' => $order->shop->shop_name,
                    'items' => $order->items,
                    'subtotal' => $order->subtotal,
                    'commission' => $order->commission_amount,
                    'total' => $order->total_amount,
                    'delivery_date' => $order->delivery_date,
                ],
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt not found',
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_NOT_FOUND);
        }
    }

    public function adminList(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $orders = FlowerOrder::query()
                ->with(['user', 'shop'])
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function adminShow(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = FlowerOrder::query()
                ->where('id', $id)
                ->with(['user', 'shop', 'items.product'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_NOT_FOUND);
        }
    }

    public function adminConfirm(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $order = $this->orderService->updateOrderStatus(
                orderId: $id,
                status: 'confirmed',
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
