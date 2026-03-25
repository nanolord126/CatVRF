<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerDelivery;
use App\Domains\Flowers\Services\FlowerDeliveryService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class FlowerDeliveryController
{
    public function __construct(
        private readonly FlowerDeliveryService $deliveryService,
        private readonly FraudControlService $fraudControlService,
    ) {}

    public function track(int $id): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $delivery = $this->deliveryService->trackDelivery($id);
            
            $this->authorize('track', $delivery);

            $this->log->channel('audit')->info('Flower delivery tracked', [
                'delivery_id' => $delivery->id,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'success' => true,
                'data' => $delivery,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found',
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_NOT_FOUND);
        }
    }

    public function orderDelivery(int $orderId): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $delivery = FlowerDelivery::query()
                ->where('order_id', $orderId)
                ->with('order')
                ->firstOrFail();

            $this->authorize('track', $delivery);

            return response()->json([
                'success' => true,
                'data' => $delivery,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Delivery not found',
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_NOT_FOUND);
        }
    }

    public function shopDeliveries(): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $shop = auth()->user()->flowerShop;
            if (!$shop) {
                return response()->json([
                    'success' => false,
                    'message' => 'Flower shop not found',
                    'correlation_id' => $correlationId,
                ], $this->response->HTTP_FORBIDDEN);
            }

            $deliveries = FlowerDelivery::query()
                ->where('shop_id', $shop->id)
                ->with('order')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $deliveries,
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

    public function assign(int $id, Request $request): JsonResponse
    {
        $correlationId = (string)Str::uuid()->toString();

        try {
            $validated = $request->validate([
                'courier_name' => 'required|string|max:255',
                'courier_phone' => 'required|string',
            ]);

            $delivery = $this->deliveryService->assignDelivery(
                orderId: $id,
                courierName: $validated['courier_name'],
                courierPhone: $validated['courier_phone'],
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $delivery,
                'correlation_id' => $correlationId,
            ], $this->response->HTTP_CREATED);
        } catch (\Exception $exception) {
            $this->log->channel('audit')->error('Delivery assignment failed', [
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

    public function update(int $id, Request $request): JsonResponse
    {
        $correlationId = Str::uuid()->toString();
        $this->fraudControlService->check(auth()->id() ?? 0, 'operation', 0, request()->ip(), null, $correlationId);

        try {
            $validated = $request->validate([
                'status' => 'required|in:in_transit,delivered,failed',
                'location' => 'nullable|array',
            ]);

            $delivery = $this->deliveryService->updateDeliveryStatus(
                deliveryId: $id,
                status: $validated['status'],
                location: $validated['location'] ?? null,
                correlationId: $correlationId,
            );

            return response()->json([
                'success' => true,
                'data' => $delivery,
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
