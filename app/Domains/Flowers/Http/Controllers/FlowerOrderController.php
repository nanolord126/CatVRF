<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerOrder;
use App\Domains\Flowers\Services\FlowerOrderService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final readonly class FlowerOrderController extends Controller
{
    public function __construct(
        private FlowerOrderService $orderService,
        private FraudControlService $fraud,
        private DatabaseManager $db,
        private LoggerInterface $logger,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'operation',
            amount: 0,
            correlationId: $correlationId,
        );

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

            $tenantId = $request->user()?->tenant_id ?? 0;

            $order = $this->orderService->createPublicOrder(
                tenantId: $tenantId,
                userId: $request->user()?->id,
                shopId: $validated['shop_id'],
                items: $validated['items'],
                deliveryData: $validated,
                correlationId: $correlationId,
            );

            return new JsonResponse([
                'success' => true,
                'data' => $order->load('items.product'),
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Order creation failed', [
                'error' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function myOrders(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $tenantId = $request->user()?->tenant_id ?? 0;

            $orders = $this->orderService->getPublicOrders(
                tenantId: $tenantId,
                userId: $request->user()?->id,
            );

            return new JsonResponse([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = FlowerOrder::query()
                ->where('id', $id)
                ->with(['user', 'shop', 'items.product', 'delivery'])
                ->firstOrFail();

            $this->authorize('view', $order);

            return new JsonResponse([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = FlowerOrder::query()->findOrFail($id);

            $this->authorize('update', $order);

            if ($order->status !== 'pending') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Cannot cancel this order',
                    'correlation_id' => $correlationId,
                ], 422);
            }

            $order = $this->db->transaction(function () use ($order, $correlationId) {
                $order->update(['status' => 'cancelled']);

                $this->logger->info('Flower order cancelled', [
                    'order_id' => $order->id,
                    'correlation_id' => $correlationId,
                ]);

                return $order;
            });

            return new JsonResponse([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function receipt(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = FlowerOrder::query()
                ->where('id', $id)
                ->with(['items.product', 'shop'])
                ->firstOrFail();

            $this->authorize('view', $order);

            return new JsonResponse([
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
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Receipt not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function adminList(): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $orders = FlowerOrder::query()
                ->with(['user', 'shop'])
                ->paginate(20);

            return new JsonResponse([
                'success' => true,
                'data' => $orders,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function adminShow(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = FlowerOrder::query()
                ->where('id', $id)
                ->with(['user', 'shop', 'items.product'])
                ->firstOrFail();

            return new JsonResponse([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Order not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function adminConfirm(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $order = $this->orderService->updateOrderStatus(
                orderId: $id,
                status: 'confirmed',
                correlationId: $correlationId,
            );

            return new JsonResponse([
                'success' => true,
                'data' => $order,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => $exception->getMessage(),
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
