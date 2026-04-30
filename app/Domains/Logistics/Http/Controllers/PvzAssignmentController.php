<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Illuminate\Contracts\Validation\Factory as ValidatorFactory;

use App\Domains\Logistics\Models\PickupPoint;
use App\Domains\Logistics\Services\PvzAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * PVZ Assignment Controller
 *
 * API endpoints for smart pickup point assignment (Ozon-level algorithm).
 * Production-ready with ML scoring, load balancing, and fraud checks.
 */
final readonly class PvzAssignmentController
{
    public function __construct(private readonly ValidatorFactory $validatorFactory,
        private readonly PvzAssignmentService $pvzService,) {}

    /**
     * Assign order to optimal pickup point.
     *
     * POST /api/v1/logistics/pvz/assign
     */
    public function assignToPvz(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'order_id' => 'nullable|integer|exists:orders,id',
            'user_id' => 'required|integer|exists:users,id',
            'user_lat' => 'required|numeric|between:-90,90',
            'user_lng' => 'required|numeric|between:-180,180',
            'amount' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());

            $pvz = $this->pvzService->assignToPvz(
                orderData: $request->all(),
                correlationId: $correlationId,
            );

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $pvz->id,
                    'uuid' => $pvz->uuid,
                    'name' => $pvz->name,
                    'address' => $pvz->address,
                    'lat' => $pvz->lat,
                    'lng' => $pvz->lng,
                    'working_hours' => $pvz->working_hours,
                    'is_24h' => $pvz->is_24h,
                    'phone' => $pvz->phone,
                    'current_load' => $pvz->current_load,
                    'capacity_slots' => $pvz->capacity_slots,
                    'load_percentage' => $pvz->getLoadPercentage(),
                    'distance_meters' => $pvz->distance_meters ?? null,
                ],
                'correlation_id' => $correlationId,
            ], 201);

        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'NO_PVZ_AVAILABLE',
                'suggestion' => 'Use courier delivery instead',
            ], 404);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
            ], 500);
        }
    }

    /**
     * Release PVZ hold (called when order is cancelled or completed).
     *
     * POST /api/v1/logistics/pvz/release-hold
     */
    public function releasePvzHold(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->all(), [
            'pvz_id' => 'required|integer|exists:pickup_points,id',
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->pvzService->releasePvzHold(
                pvzId: (int) $request->input('pvz_id'),
                orderId: $request->input('order_id') ? (int) $request->input('order_id') : null,
            );

            return new JsonResponse([
                'success' => true,
                'message' => 'PVZ hold released successfully',
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get nearby pickup points with availability.
     *
     * GET /api/v1/logistics/pvz/nearby?lat=&lng=&radius=
     */
    public function getNearbyPvz(Request $request): JsonResponse
    {
        $validator = $this->validatorFactory->make($request->query(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:10',
        ]);

        if ($validator->fails()) {
            return new JsonResponse([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tenantId = $request->get('tenant_id') ?? (function_exists('tenant') ? tenant()->id : 1);
            $lat = (float) $request->query('lat');
            $lng = (float) $request->query('lng');
            $radiusMeters = (int) (($request->query('radius') ?? 3) * 1000);

            $pvzs = PickupPoint::query()
                ->where('tenant_id', $tenantId)
                ->where('status', PickupPoint::STATUS_ACTIVE)
                ->selectRaw('
                    *,
                    (
                        6371000 * ACOS(
                            COS(RADIANS(?)) * COS(RADIANS(lat)) *
                            COS(RADIANS(lng) - RADIANS(?)) +
                            SIN(RADIANS(?)) * SIN(RADIANS(lat))
                        )
                    ) as distance_meters
                ', [$lat, $lng, $lat])
                ->having('distance_meters', '<=', $radiusMeters)
                ->orderBy('distance_meters')
                ->limit(20)
                ->get();

            return new JsonResponse([
                'success' => true,
                'data' => $pvzs->map(fn (PickupPoint $pvz) => [
                    'id' => $pvz->id,
                    'uuid' => $pvz->uuid,
                    'name' => $pvz->name,
                    'address' => $pvz->address,
                    'lat' => $pvz->lat,
                    'lng' => $pvz->lng,
                    'working_hours' => $pvz->working_hours,
                    'is_24h' => $pvz->is_24h,
                    'phone' => $pvz->phone,
                    'current_load' => $pvz->current_load,
                    'capacity_slots' => $pvz->capacity_slots,
                    'load_percentage' => $pvz->getLoadPercentage(),
                    'is_open' => $pvz->isOpen(),
                    'has_slots' => $pvz->hasAvailableSlots(),
                    'is_near_overload' => $pvz->isNearOverload(),
                    'distance_meters' => $pvz->distance_meters,
                    'distance_km' => round($pvz->distance_meters / 1000, 2),
                ]),
                'count' => $pvzs->count(),
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Internal server error',
            ], 500);
        }
    }
}
