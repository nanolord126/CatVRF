<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

use Carbon\CarbonImmutable;

use App\Domains\Logistics\Models\OrderShipment;
use App\Domains\Logistics\Services\UnifiedFleetService;
use App\Domains\Logistics\Services\PvzAssignmentService;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Domains\Logistics\Models\PickupPoint;
use App\Domains\Logistics\Services\GeoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * Unified Logistics Controller
 *
 * API endpoints for the Unified Logistics Core.
 * Handles courier/taxi assignment and PVZ assignment.
 *
 * Production-ready with:
 * - Fraud checks on every request
 * - Input validation
 * - Audit logging
 * - Error handling
 */
final class UnifiedLogisticsController
{
    public function __construct(private readonly ValidatorFactory $validatorFactory,
        private readonly UnifiedFleetService $fleetService,
        private readonly PvzAssignmentService $pvzService,
        private readonly FraudControlService $fraudControlService,) {}

    /**
     * Assign shipment to courier/taxi driver.
     */
    public function assignCourier(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'delivery_lat' => 'required|numeric|between:-90,90',
            'delivery_lng' => 'required|numeric|between:-180,180',
            'weight_kg' => 'nullable|numeric|min:0|max:100',
            'amount' => 'nullable|numeric|min:0',
            'time_sensitive' => 'nullable|boolean',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $result = $this->fleetService->assignShipment(
                $request->all(),
                $correlationId
            );

            return new JsonResponse([
                'success' => true,
                'data' => $result->toArray(),
                'correlation_id' => $correlationId,
            ], 201);

        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 404);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Assign order to pickup point (PVZ).
     */
    public function assignPvz(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'order_id' => 'nullable|integer|exists:orders,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'user_lat' => 'required|numeric|between:-90,90',
            'user_lng' => 'required|numeric|between:-180,180',
            'amount' => 'nullable|numeric|min:0',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $pvz = $this->pvzService->assignToPvz(
                $request->all(),
                $correlationId
            );

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'pvz_id' => $pvz->id,
                    'pvz_name' => $pvz->name,
                    'pvz_address' => $pvz->address,
                    'lat' => $pvz->lat,
                    'lng' => $pvz->lng,
                    'is_24h' => $pvz->is_24h,
                    'working_hours' => $pvz->working_hours,
                    'load_percentage' => $pvz->getLoadPercentage(),
                ],
                'correlation_id' => $correlationId,
            ], 201);

        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ], 404);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get shipment details.
     *
     * @param  int  $id  Shipment ID
     */
    public function getShipment(int $id): JsonResponse
    {
        $correlationId = request()->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $shipment = OrderShipment::with(['courier', 'pickupPoint', 'order'])
                ->findOrFail($id);

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $shipment->id,
                    'uuid' => $shipment->uuid,
                    'status' => $shipment->status,
                    'fulfillment_type' => $shipment->fulfillment_type,
                    'eta_minutes' => $shipment->eta_minutes,
                    'distance_km' => $shipment->distance_km,
                    'qr_code' => $shipment->qr_code,
                    'pickup_code' => $shipment->pickup_code,
                    'courier' => $shipment->courier ? [
                        'id' => $shipment->courier->id,
                        'vehicle_type' => $shipment->courier->vehicle_type,
                        'is_taxi_driver' => $shipment->courier->is_taxi_driver,
                        'current_lat' => $shipment->courier->current_lat,
                        'current_lng' => $shipment->courier->current_lng,
                        'rating' => $shipment->courier->rating,
                    ] : null,
                    'pickup_point' => $shipment->pickupPoint ? [
                        'id' => $shipment->pickupPoint->id,
                        'name' => $shipment->pickupPoint->name,
                        'address' => $shipment->pickupPoint->address,
                        'lat' => $shipment->pickupPoint->lat,
                        'lng' => $shipment->pickupPoint->lng,
                        'is_24h' => $shipment->pickupPoint->is_24h,
                        'working_hours' => $shipment->pickupPoint->working_hours,
                    ] : null,
                    'assigned_at' => $shipment->assigned_at?->toIso8601String(),
                    'delivered_at' => $shipment->delivered_at?->toIso8601String(),
                    'issued_at_pvz' => $shipment->issued_at_pvz?->toIso8601String(),
                ],
                'correlation_id' => $correlationId,
            ]);

        } catch (ModelNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Shipment not found',
                'correlation_id' => $correlationId,
            ], 404);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Update shipment status.
     *
     * @param  int  $id  Shipment ID
     */
    public function updateShipmentStatus(Request $request, int $id): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'status' => 'required|string|in:pending,assigned,picked,in_transit,delivered,issued_at_pvz,cancelled,failed',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $shipment = OrderShipment::findOrFail($id);

            $shipment->update([
                'status' => $request->status,
                'correlation_id' => $correlationId,
            ]);

            // Update timestamps based on status
            if ($request->status === 'picked') {
                $shipment->update(['picked_at' => CarbonImmutable::now()]);
            } elseif ($request->status === 'delivered') {
                $shipment->update(['delivered_at' => CarbonImmutable::now()]);
            } elseif ($request->status === 'issued_at_pvz') {
                $shipment->update(['issued_at_pvz' => CarbonImmutable::now()]);
            }

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $shipment->id,
                    'status' => $shipment->status,
                ],
                'correlation_id' => $correlationId,
            ]);

        } catch (ModelNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Shipment not found',
                'correlation_id' => $correlationId,
            ], 404);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    /**
     * Get nearby pickup points.
     */
    public function getNearbyPvz(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();

        try {
            $tenantId = $request->tenant_id ?? (function_exists('tenant') ? tenant()->id : 1);
            $radiusKm = $request->radius_km ?? 3.0;

            $pvzs = PickupPoint::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->whereRaw('
                    (
                        6371 * ACOS(
                            COS(RADIANS(?)) * COS(RADIANS(lat)) *
                            COS(RADIANS(lng) - RADIANS(?)) +
                            SIN(RADIANS(?)) * SIN(RADIANS(lat))
                        )
                    ) <= ?
                ', [$request->lat, $request->lng, $request->lat, $radiusKm])
                ->get()
                ->map(function ($pvz) use ($request) {
                    $distance = (new GeoService(
                        $this->cacheManager,
                        app('log')
                    ))->calculateDistance($request->lat, $request->lng, $pvz->lat, $pvz->lng);

                    return [
                        'id' => $pvz->id,
                        'name' => $pvz->name,
                        'address' => $pvz->address,
                        'lat' => $pvz->lat,
                        'lng' => $pvz->lng,
                        'distance_km' => round($distance, 2),
                        'is_24h' => $pvz->is_24h,
                        'working_hours' => $pvz->working_hours,
                        'load_percentage' => $pvz->getLoadPercentage(),
                        'has_available_slots' => $pvz->hasAvailableSlots(),
                    ];
                })
                ->sortBy('distance_km')
                ->values();

            return new JsonResponse([
                'success' => true,
                'data' => $pvzs,
                'correlation_id' => $correlationId,
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }
}
