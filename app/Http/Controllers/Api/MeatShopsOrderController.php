<?php
declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Domains\MeatShops\Models\MeatOrder;
use App\Domains\MeatShops\Services\MeatShopsService;
use App\Http\Requests\MeatShops\StoreOrderRequest;
use App\Services\FraudControlService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class MeatShopsOrderController extends BaseApiController
{
    public function __construct(
        private readonly MeatShopsService $service,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            $correlationId = Str::uuid()->toString();
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $orders = MeatOrder::where('tenant_id', $tenantId)
                ->with('product')
                ->paginate(20);

            return $this->successResponse($orders);
        } catch (\Exception $e) {
            Log::channel('audit')->error('MeatShops orders list error', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to fetch orders', 500);
        }
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'meatshops_order_store', 0, $request->ip(), null, $correlationId);

        try {
            $tenantId = auth()->user()?->tenant_id ?? tenant()->id;

            $order = $this->service->createOrder(
                productId: $request->integer('product_id'),
                clientId: $request->integer('client_id'),
                weightKg: (float) $request->input('weight_kg'),
                deliveryDate: Carbon::parse($request->input('delivery_date')),
                tenantId: $tenantId,
                correlationId: $correlationId,
            );

            Log::channel('audit')->info('MeatShops order created', ['order_id' => $order->id]);

            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            Log::channel('audit')->error('MeatShops order creation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to create order: ' . $e->getMessage(), 400);
        }
    }
}
