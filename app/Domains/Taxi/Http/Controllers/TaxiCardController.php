<?php declare(strict_types=1);

namespace App\Domains\Taxi\Http\Controllers;

use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Models\TaxiVehicle;
use App\Domains\Taxi\Models\TaxiRide;
use App\Domains\Taxi\Models\TaxiTariff;
use App\Domains\Taxi\Resources\TaxiDriverResource;
use App\Domains\Taxi\Resources\TaxiVehicleResource;
use App\Domains\Taxi\Resources\TaxiRideResource;
use App\Domains\Taxi\Resources\TaxiTariffResource;
use App\Domains\Taxi\Resources\TaxiPassengerResource;
use App\Services\FraudControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * TaxiCardController - API endpoints for taxi cards
 * Classic taxi-style cards like Yandex.Taxi
 */
final readonly class TaxiCardController
{
    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly Cache $cache,
    ) {}

    public function getDriverCard(int $driverId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_driver_card_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "taxi:driver:card:{$driverId}";
        $cachedCard = $this->cache->get($cacheKey);
        
        if ($cachedCard !== null) {
            return response()->json([
                'success' => true,
                'data' => $cachedCard,
                'correlation_id' => $correlationId,
            ]);
        }

        $driver = TaxiDriver::with(['vehicles'])
            ->where('id', $driverId)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        $card = (new TaxiDriverResource($driver))->toArray($request);
        
        $this->cache->put($cacheKey, $card, 300);

        return response()->json([
            'success' => true,
            'data' => $card,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getVehicleCard(int $vehicleId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_vehicle_card_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "taxi:vehicle:card:{$vehicleId}";
        $cachedCard = $this->cache->get($cacheKey);
        
        if ($cachedCard !== null) {
            return response()->json([
                'success' => true,
                'data' => $cachedCard,
                'correlation_id' => $correlationId,
            ]);
        }

        $vehicle = TaxiVehicle::where('id', $vehicleId)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        $card = (new TaxiVehicleResource($vehicle))->toArray($request);
        
        $this->cache->put($cacheKey, $card, 300);

        return response()->json([
            'success' => true,
            'data' => $card,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getTariffs(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_tariffs_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $cacheKey = "taxi:tariffs:" . tenant()->id;
        $cachedTariffs = $this->cache->get($cacheKey);
        
        if ($cachedTariffs !== null) {
            return response()->json([
                'success' => true,
                'data' => $cachedTariffs,
                'correlation_id' => $correlationId,
            ]);
        }

        $tariffs = TaxiTariff::where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->get();

        $tariffsData = TaxiTariffResource::collection($tariffs)->toArray($request);
        
        $this->cache->put($cacheKey, $tariffsData, 180);

        return response()->json([
            'success' => true,
            'data' => $tariffsData,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getTariff(int $tariffId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_tariff_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $tariff = TaxiTariff::where('id', $tariffId)
            ->where('tenant_id', tenant()->id)
            ->where('is_active', true)
            ->firstOrFail();

        $tariffData = (new TaxiTariffResource($tariff))->toArray($request);

        return response()->json([
            'success' => true,
            'data' => $tariffData,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getPassengerProfile(int $passengerId, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_passenger_profile_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $passenger = $request->user();
        
        if ($passenger === null || $passenger->id !== $passengerId) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 403);
        }

        $cacheKey = "taxi:passenger:profile:{$passengerId}";
        $cachedProfile = $this->cache->get($cacheKey);
        
        if ($cachedProfile !== null) {
            return response()->json([
                'success' => true,
                'data' => $cachedProfile,
                'correlation_id' => $correlationId,
            ]);
        }

        $profile = (new TaxiPassengerResource($passenger))->toArray($request);
        
        $this->cache->put($cacheKey, $profile, 600);

        return response()->json([
            'success' => true,
            'data' => $profile,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getRideCard(string $rideUuid, Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_ride_card_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $ride = TaxiRide::with(['passenger', 'driver', 'vehicle'])
            ->where('uuid', $rideUuid)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        $user = $request->user();
        
        if ($user !== null && $ride->passenger_id !== $user->id && $ride->driver_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized',
                'correlation_id' => $correlationId,
            ], 403);
        }

        $rideData = (new TaxiRideResource($ride))->toArray($request);

        return response()->json([
            'success' => true,
            'data' => $rideData,
            'correlation_id' => $correlationId,
        ]);
    }

    public function getNearbyDrivers(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', '');
        
        $this->fraud->check(
            userId: $request->user()?->id ?? 0,
            operationType: 'taxi_nearby_drivers_view',
            amount: 0,
            ipAddress: $request->ip(),
            deviceFingerprint: $request->header('X-Device-Fingerprint'),
            correlationId: $correlationId,
        );

        $lat = (float) $request->input('lat');
        $lon = (float) $request->input('lon');
        $radius = (float) $request->input('radius', 5.0);
        $limit = (int) $request->input('limit', 20);

        $drivers = TaxiDriver::with(['vehicles'])
            ->where('tenant_id', tenant()->id)
            ->where('status', 'active')
            ->where('is_online', true)
            ->where('is_blocked', false)
            ->where('rating', '>=', 4.0)
            ->whereBetween('current_lat', [$lat - $radius, $lat + $radius])
            ->whereBetween('current_lon', [$lon - $radius, $lon + $radius])
            ->limit($limit)
            ->get();

        $driversData = TaxiDriverResource::collection($drivers)->toArray($request);

        return response()->json([
            'success' => true,
            'data' => [
                'drivers' => $driversData,
                'count' => count($driversData),
                'search_center' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'radius_km' => $radius,
                ],
            ],
            'correlation_id' => $correlationId,
        ]);
    }
}
