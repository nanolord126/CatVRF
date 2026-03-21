<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Domains\Furniture\Models\FurnitureOrder;
use App\Domains\Furniture\Services\DeliveryAssemblyService;
use App\Http\Requests\Furniture\StoreOrderRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class FurnitureOrderController extends BaseApiController
{
    public function __construct(
        private DeliveryAssemblyService $service,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $orders = FurnitureOrder::where('tenant_id', $tenantId)
                ->with('item')
                ->paginate(20);

            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Furniture orders list error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch orders', 500);
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

            $order = new FurnitureOrder([
                'tenant_id' => $tenantId,
                'uuid' => Str::uuid(),
                'correlation_id' => $correlationId,
                'item_id' => $request->integer('item_id'),
                'client_id' => $request->integer('client_id'),
                'client_address' => $request->string('client_address'),
                'delivery_date' => Carbon::parse($request->input('delivery_date')),
                'status' => 'pending',
            ]);
            $order->save();

            Log::channel('audit')->info('Furniture order created', ['order_id' => $order->id]);

            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('Furniture order creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
        }
    }

    public function scheduleDelivery(int $id): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = FurnitureOrder::where('tenant_id', $tenantId)->findOrFail($id);

            $this->service->scheduleDelivery(
                orderId: $id,
                tenantId: $tenantId,
                deliveryDate: $order->delivery_date,
                needsAssembly: $order->assembly_date !== null,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('Furniture delivery scheduled', ['order_id' => $id]);

            return $this->successResponse($order->refresh(), 'Delivery scheduled');
        } catch (\Exception $e) {
            Log::channel('audit')->error('Furniture delivery schedule failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to schedule delivery', 400);
        }
    }
}
