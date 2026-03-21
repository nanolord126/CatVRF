<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Domains\FarmDirect\Models\FarmOrder;
use App\Domains\FarmDirect\Models\FarmProduct;
use App\Domains\FarmDirect\Services\FarmDirectService;
use App\Http\Requests\FarmDirect\StoreOrderRequest;
use App\Http\Requests\FarmDirect\UpdateOrderRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FarmDirectOrderController extends BaseApiController
{
    public function __construct(
        private FarmDirectService $service,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            Log::channel('audit')->info('FarmDirect orders list', [
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'user_id' => auth()->id(),
            ]);

            $orders = FarmOrder::where('tenant_id', $tenantId)
                ->with(['product', 'farm'])
                ->paginate(20);

            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::channel('audit')->error('FarmDirect orders list error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to fetch orders', 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = FarmOrder::where('tenant_id', $tenantId)->findOrFail($id);

            Log::channel('audit')->info('FarmDirect order viewed', [
                'order_id' => $id,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $this->successResponse($order->load(['product', 'farm']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Order not found', 404);
        } catch (\Exception $e) {
            Log::channel('audit')->error('FarmDirect order show error', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to fetch order', 500);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;
            $clientId = auth()->id() ?? 0;

            $order = $this->service->createOrder(
                productId: $request->integer('product_id'),
                clientId: $clientId,
                quantityKg: (float) $request->input('quantity_kg'),
                deliveryDate: Carbon::parse($request->input('delivery_date')),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('FarmDirect order created', [
                'order_id' => $order->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
                'amount' => $order->total_price,
            ]);

            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('FarmDirect order creation failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
        }
    }

    public function update(int $id, UpdateOrderRequest $request): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = FarmOrder::where('tenant_id', $tenantId)->findOrFail($id);

            if ($order->status !== 'pending') {
                return $this->errorResponse('Can only update pending orders', 400);
            }

            $order->update($request->validated());

            Log::channel('audit')->info('FarmDirect order updated', [
                'order_id' => $id,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $this->successResponse($order, 'Order updated successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Order not found', 404);
        } catch (\Exception $e) {
            Log::channel('audit')->error('FarmDirect order update failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update order', 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        if (class_exists('\App\Services\FraudControlService')) {
            \App\Services\FraudControlService::check();
        }

        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = FarmOrder::where('tenant_id', $tenantId)->findOrFail($id);

            if ($order->status !== 'pending') {
                return $this->errorResponse('Can only delete pending orders', 400);
            }

            $order->delete();

            Log::channel('audit')->info('FarmDirect order deleted', [
                'order_id' => $id,
                'tenant_id' => $tenantId,
                'correlation_id' => $correlationId,
            ]);

            return $this->successResponse(null, 'Order deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->errorResponse('Order not found', 404);
        } catch (\Exception $e) {
            Log::channel('audit')->error('FarmDirect order deletion failed', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete order', 500);
        }
    }
}
