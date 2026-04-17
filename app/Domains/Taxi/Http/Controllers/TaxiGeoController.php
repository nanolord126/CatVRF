<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Services\TaxiGeoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class TaxiGeoController extends Controller
{
    public function __construct(
        private readonly TaxiGeoService $geoService,
    ) {}

    public function calculateRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lon' => 'required|numeric|between:-180,180',
            'dropoff_lat' => 'required|numeric|between:-90,90',
            'dropoff_lon' => 'required|numeric|between:-180,180',
            'waypoints' => 'nullable|array',
        ]);

        $route = $this->geoService->calculateRoute(
            pickupLat: $validated['pickup_lat'],
            pickupLon: $validated['pickup_lon'],
            dropoffLat: $validated['dropoff_lat'],
            dropoffLon: $validated['dropoff_lon'],
            waypoints: $validated['waypoints'] ?? null,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'route' => $route,
        ]);
    }

    public function calculateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat1' => 'required|numeric|between:-90,90',
            'lon1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lon2' => 'required|numeric|between:-180,180',
        ]);

        $distance = $this->geoService->calculateDistance(
            lat1: $validated['lat1'],
            lon1: $validated['lon1'],
            lat2: $validated['lat2'],
            lon2: $validated['lon2'],
        );

        return response()->json([
            'success' => true,
            'distance_meters' => $distance,
        ]);
    }

    public function estimateDuration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lon' => 'required|numeric|between:-180,180',
            'dropoff_lat' => 'required|numeric|between:-90,90',
            'dropoff_lon' => 'required|numeric|between:-180,180',
        ]);

        $duration = $this->geoService->estimateDuration(
            pickupLat: $validated['pickup_lat'],
            pickupLon: $validated['pickup_lon'],
            dropoffLat: $validated['dropoff_lat'],
            dropoffLon: $validated['dropoff_lon'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'duration_seconds' => $duration,
        ]);
    }

    public function findNearbyDrivers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meters' => 'nullable|integer|min:100|max:10000',
            'vehicle_class' => 'nullable|string',
        ]);

        $drivers = $this->geoService->findNearbyDrivers(
            latitude: $validated['latitude'],
            longitude: $validated['longitude'],
            radiusMeters: $validated['radius_meters'] ?? 3000,
            vehicleClass: $validated['vehicle_class'] ?? null,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'drivers' => $drivers,
        ]);
    }

    public function predictPickupETA(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_lat' => 'required|numeric|between:-90,90',
            'driver_lon' => 'required|numeric|between:-180,180',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lon' => 'required|numeric|between:-180,180',
        ]);

        $eta = $this->geoService->predictPickupETA(
            driverLat: $validated['driver_lat'],
            driverLon: $validated['driver_lon'],
            pickupLat: $validated['pickup_lat'],
            pickupLon: $validated['pickup_lon'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'eta' => $eta,
        ]);
    }

    public function createGeoZone(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:city,district,airport,station,business,residential,restricted',
            'polygon' => 'nullable|array',
            'center_latitude' => 'nullable|numeric|between:-90,90',
            'center_longitude' => 'nullable|numeric|between:-180,180',
            'radius_meters' => 'nullable|numeric|min:0',
            'base_price_multiplier' => 'nullable|numeric|min:0',
            'min_price_kopeki' => 'nullable|integer|min:0',
            'max_price_kopeki' => 'nullable|integer|min:0',
            'surge_enabled' => 'nullable|boolean',
            'surge_multiplier_default' => 'nullable|numeric|min:1',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:10',
        ]);

        $zone = $this->geoService->createGeoZone(
            data: $validated,
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'zone' => $zone,
        ]);
    }

    public function getActiveZones(Request $request): JsonResponse
    {
        $zones = $this->geoService->getActiveZones(
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'zones' => $zones,
        ]);
    }

    public function updateDriverLocation(Request $request, int $driverId): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->geoService->updateDriverLocation(
            driverId: $driverId,
            latitude: $validated['latitude'],
            longitude: $validated['longitude'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Driver location updated',
        ]);
    }

    public function getPricingMultipliers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $multipliers = $this->geoService->getPricingMultipliers(
            latitude: $validated['latitude'],
            longitude: $validated['longitude'],
            correlationId: $request->header('X-Correlation-ID'),
        );

        return response()->json([
            'success' => true,
            'multipliers' => $multipliers,
        ]);
    }
}
