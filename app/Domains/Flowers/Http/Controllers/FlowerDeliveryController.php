<?php declare(strict_types=1);

namespace App\Domains\Flowers\Http\Controllers;

use App\Domains\Flowers\Models\FlowerDelivery;
use App\Domains\Flowers\Services\FlowerDeliveryService;
use App\Http\Controllers\Controller;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class FlowerDeliveryController extends Controller
{
    public function __construct(
        private FlowerDeliveryService $deliveryService,
        private FraudControlService $fraud,
        private LoggerInterface $logger,
    ) {}

    public function track(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $delivery = $this->deliveryService->trackDelivery($id);

            $this->authorize('track', $delivery);

            $this->logger->info('Flower delivery tracked', [
                'delivery_id' => $delivery->id,
                'correlation_id' => $correlationId,
            ]);

            return new JsonResponse([
                'success' => true,
                'data' => $delivery,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Delivery not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function orderDelivery(int $orderId): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $delivery = FlowerDelivery::query()
                ->where('order_id', $orderId)
                ->with('order')
                ->firstOrFail();

            $this->authorize('track', $delivery);

            return new JsonResponse([
                'success' => true,
                'data' => $delivery,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Delivery not found',
                'correlation_id' => $correlationId,
            ], 404);
        }
    }

    public function shopDeliveries(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

        try {
            $shop = $request->user()->flowerShop;
            if (!$shop) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Flower shop not found',
                    'correlation_id' => $correlationId,
                ], 403);
            }

            $deliveries = FlowerDelivery::query()
                ->where('shop_id', $shop->id)
                ->with('order')
                ->paginate(15);

            return new JsonResponse([
                'success' => true,
                'data' => $deliveries,
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

    public function assign(int $id, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());

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

            return new JsonResponse([
                'success' => true,
                'data' => $delivery,
                'correlation_id' => $correlationId,
            ], 201);
        } catch (\Throwable $exception) {
            $this->logger->error('Delivery assignment failed', [
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

    public function update(int $id, Request $request): JsonResponse
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
                'status' => 'required|in:in_transit,delivered,failed',
                'location' => 'nullable|array',
            ]);

            $delivery = $this->deliveryService->updateDeliveryStatus(
                deliveryId: $id,
                status: $validated['status'],
                location: $validated['location'] ?? null,
                correlationId: $correlationId,
            );

            return new JsonResponse([
                'success' => true,
                'data' => $delivery,
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
