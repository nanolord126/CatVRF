<?php

declare(strict_types=1);

namespace App\Domains\GeoLogistics\Presentation\Http\Controllers;

use App\Domains\GeoLogistics\Application\DTOs\CreateShipmentDto;
use App\Domains\GeoLogistics\Application\UseCases\CreateShipmentRouteUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Презентационный слой: REST API для создания доставки (интеграция из Cart/Checkout).
 */
final class CreateShipmentController extends Controller
{
    public function __construct(
        private readonly CreateShipmentRouteUseCase $useCase) {
        $this->middleware('throttle:10,1');
    }

    public function __invoke(Request $request): JsonResponse
    {
        // В продакшене валидировать через FormRequest, здесь демо-массив.
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer'],
            'delivery_order_id' => ['required', 'integer'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $correlationId = (string) Str::uuid();

        $dto = new CreateShipmentDto(
            tenantId: (int) $validated['tenant_id'],
            deliveryOrderId: (int) $validated['delivery_order_id'],
            pickupLat: (float) $validated['pickup_lat'],
            pickupLng: (float) $validated['pickup_lng'],
            dropoffLat: (float) $validated['dropoff_lat'],
            dropoffLng: (float) $validated['dropoff_lng'],
            correlationId: $correlationId,
        );

        $shipment = $this->useCase->execute($dto);

        return new \Illuminate\Http\JsonResponse([
            'success' => true,
            'correlation_id' => $correlationId,
            'data' => [
                'shipment_id' => $shipment->id,
                'uuid' => $shipment->uuid,
                'status' => $shipment->status->value,
                'estimated_distance_meters' => $shipment->estimated_distance_meters,
                'estimated_duration_seconds' => $shipment->estimated_duration_seconds,
                'calculated_cost' => $shipment->calculated_cost,
            ]
        ], 201);
    }
}
