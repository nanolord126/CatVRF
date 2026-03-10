<?php

namespace App\Domains\Delivery\Http\Controllers;

use App\Domains\Delivery\Models\DeliveryOrder;
use App\Domains\Delivery\Services\DeliveryService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeliveryController extends Controller
{
    public function __construct(private DeliveryService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeliveryOrder::class);
        return response()->json(
            DeliveryOrder::where('tenant_id', tenant()->id)->paginate($request->input('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DeliveryOrder::class);
        return response()->json($this->service->createOrder($request->all()), 201);
    }

    public function show(DeliveryOrder $order): JsonResponse
    {
        $this->authorize('view', $order);
        return response()->json($order);
    }

    public function update(Request $request, DeliveryOrder $order): JsonResponse
    {
        $this->authorize('update', $order);
        return response()->json($this->service->assignDriver($order, $request->input('driver_id')));
    }

    public function destroy(DeliveryOrder $order): JsonResponse
    {
        $this->authorize('delete', $order);
        $order->delete();
        return response()->json(['message' => 'Order deleted']);
    }
}
