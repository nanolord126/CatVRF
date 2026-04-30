<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Http\Controllers;

use Symfony\Component\HttpKernel\Exception\HttpException;

use App\Domains\Logistics\Models\PickupPoint;
use App\Domains\Logistics\Services\PvzAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

/**
 * Pickup Point Controller
 *
 * API endpoints for managing pickup points (ПВЗ).
 * Follows CatVRF API standards with tenant scoping.
 */
final readonly class PickupPointController
{
    public function __construct(
        private readonly PvzAssignmentService $pvzAssignmentService,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
    ) {}

    /**
     * List all pickup points for the tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

        $pickupPoints = PickupPoint::query()
            ->where('tenant_id', $tenantId)
            ->where('status', PickupPoint::STATUS_ACTIVE)
            ->get();

        return new JsonResponse([
            'data' => $pickupPoints,
            'meta' => [
                'total' => $pickupPoints->count(),
            ],
        ]);
    }

    /**
     * Show a specific pickup point.
     */
    public function show(PickupPoint $pickupPoint): JsonResponse
    {
        $this->authorizeTenantAccess($pickupPoint);

        return new JsonResponse([
            'data' => $pickupPoint->load('shipments'),
        ]);
    }

    /**
     * Find pickup points nearby user location.
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.5|max:10',
        ]);

        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;
        $radiusKm = $validated['radius_km'] ?? 3.0;
        $radiusMeters = $radiusKm * 1000;

        $pickupPoints = PickupPoint::query()
            ->where('tenant_id', $tenantId)
            ->where('status', PickupPoint::STATUS_ACTIVE)
            ->where('current_load', '<', $this->db->raw('capacity_slots * 0.85'))
            ->selectRaw('
                *,
                (
                    6371000 * ACOS(
                        COS(RADIANS(?)) * COS(RADIANS(lat)) *
                        COS(RADIANS(lng) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(lat))
                    )
                ) as distance_meters
            ', [$validated['lat'], $validated['lng'], $validated['lat']])
            ->having('distance_meters', '<=', $radiusMeters)
            ->orderBy('distance_meters')
            ->limit(15)
            ->get();

        return new JsonResponse([
            'data' => $pickupPoints,
            'meta' => [
                'total' => $pickupPoints->count(),
                'search_radius_km' => $radiusKm,
            ],
        ]);
    }

    /**
     * Assign order to optimal pickup point.
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'user_lat' => 'required|numeric|between:-90,90',
            'user_lng' => 'required|numeric|between:-180,180',
            'amount' => 'nullable|numeric',
        ]);

        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;

        try {
            $pickupPoint = $this->pvzAssignmentService->assignToPvz([
                'tenant_id' => $tenantId,
                'order_id' => $validated['order_id'],
                'user_id' => $this->guard->id(),
                'user_lat' => $validated['user_lat'],
                'user_lng' => $validated['user_lng'],
                'amount' => $validated['amount'] ?? 0,
            ], $correlationId);

            return new JsonResponse([
                'data' => $pickupPoint,
                'message' => 'Pickup point assigned successfully',
            ], 201);

        } catch (\RuntimeException $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Authorize tenant access to pickup point.
     */
    private function authorizeTenantAccess(PickupPoint $pickupPoint): void
    {
        $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;
        if ($pickupPoint->tenant_id !== $tenantId) {
            throw new HttpException(403'Access denied to this pickup point');
        }
    }
}
