<?php declare(strict_types=1);

namespace Modules\Taxi\Services;

use App\Services\AuditService;
use App\Services\ML\UserBehaviorAnalyzerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Taxi\Models\TaxiDriver;
use Modules\Taxi\Models\TaxiVehicle;

final readonly class DriverMatchingService
{
    private const CACHE_TTL = 120;
    private const MATCH_RADIUS_METERS = 5000;
    private const MAX_DRIVERS_TO_RETURN = 5;

    public function __construct(
        private AuditService $audit,
        private ?UserBehaviorAnalyzerService $behaviorAnalyzer = null,
    ) {}

    public function findBestDrivers(
        float $pickupLatitude,
        float $pickupLongitude,
        ?string $vehicleClass = null,
        ?int $userId = null,
        string $correlationId = 'default',
    ): array {
        $cacheKey = "taxi:drivers:{$pickupLatitude}:{$pickupLongitude}:{$vehicleClass}";
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $query = TaxiDriver::where('status', 'available')
            ->where('is_online', true)
            ->where('is_verified', true)
            ->where('rating', '>=', 4.0);

        if ($vehicleClass !== null) {
            $query->whereHas('vehicles', function ($q) use ($vehicleClass) {
                $q->where('vehicle_class', $vehicleClass)
                  ->where('status', 'available');
            });
        }

        $drivers = $query->get();

        $scoredDrivers = [];

        foreach ($drivers as $driver) {
            $distance = $this->calculateDistance(
                $pickupLatitude,
                $pickupLongitude,
                $driver->current_latitude,
                $driver->current_longitude,
            );

            if ($distance > self::MATCH_RADIUS_METERS) {
                continue;
            }

            $score = $this->calculateDriverScore($driver, $distance, $userId);

            $scoredDrivers[] = [
                'driver_id' => $driver->id,
                'driver_uuid' => $driver->uuid,
                'name' => $driver->name,
                'rating' => $driver->rating,
                'total_rides' => $driver->total_rides,
                'vehicle' => $driver->vehicles->first(),
                'distance_meters' => $distance,
                'eta_minutes' => round($distance / 250),
                'score' => $score,
                'streak' => $driver->current_streak,
            ];
        }

        usort($scoredDrivers, fn ($a, $b) => $b['score'] <=> $a['score']);
        $topDrivers = array_slice($scoredDrivers, 0, self::MAX_DRIVERS_TO_RETURN);

        Redis::setex($cacheKey, self::CACHE_TTL, json_encode($topDrivers));

        $this->audit->record('taxi_driver_match_search', 'DriverMatching', null, [], [
            'correlation_id' => $correlationId,
            'pickup_latitude' => $pickupLatitude,
            'pickup_longitude' => $pickupLongitude,
            'vehicle_class' => $vehicleClass,
            'drivers_found' => count($topDrivers),
        ], $correlationId);

        return $topDrivers;
    }

    public function assignDriver(int $rideId, int $driverId, string $correlationId): void
    {
        DB::transaction(function () use ($rideId, $driverId, $correlationId) {
            DB::table('taxi_rides')
                ->where('id', $rideId)
                ->update([
                    'driver_id' => $driverId,
                    'status' => 'driver_assigned',
                    'driver_assigned_at' => now(),
                ]);

            DB::table('taxi_drivers')
                ->where('id', $driverId)
                ->update([
                    'status' => 'busy',
                    'current_ride_id' => $rideId,
                ]);

            DB::table('taxi_drivers')
                ->where('id', $driverId)
                ->increment('current_streak');

            $this->audit->record('taxi_driver_assigned', 'TaxiRide', $rideId, [], [
                'correlation_id' => $correlationId,
                'ride_id' => $rideId,
                'driver_id' => $driverId,
            ], $correlationId);

            Log::channel('audit')->info('Driver assigned to ride', [
                'correlation_id' => $correlationId,
                'ride_id' => $rideId,
                'driver_id' => $driverId,
            ]);
        });
    }

    public function releaseDriver(int $driverId, string $correlationId): void
    {
        DB::transaction(function () use ($driverId, $correlationId) {
            DB::table('taxi_drivers')
                ->where('id', $driverId)
                ->update([
                    'status' => 'available',
                    'current_ride_id' => null,
                ]);

            $this->audit->record('taxi_driver_released', 'TaxiDriver', $driverId, [], [
                'correlation_id' => $correlationId,
                'driver_id' => $driverId,
            ], $correlationId);
        });
    }

    private function calculateDriverScore(TaxiDriver $driver, float $distance, ?int $userId): float
    {
        $distanceScore = max(0, 1 - ($distance / self::MATCH_RADIUS_METERS));
        $ratingScore = $driver->rating / 5.0;
        $experienceScore = min($driver->total_rides / 1000, 1.0);
        $availabilityScore = $driver->acceptance_rate / 100;
        $streakBonus = min($driver->current_streak / 20, 0.2);

        $userPreferenceScore = 0;
        if ($userId !== null && $this->behaviorAnalyzer !== null) {
            $userPreferenceScore = $this->calculateUserPreferenceScore($driver->id, $userId);
        }

        return (
            $distanceScore * 0.35 +
            $ratingScore * 0.25 +
            $experienceScore * 0.15 +
            $availabilityScore * 0.15 +
            $streakBonus +
            $userPreferenceScore * 0.1
        );
    }

    private function calculateUserPreferenceScore(int $driverId, int $userId): float
    {
        $previousRides = DB::table('taxi_rides')
            ->where('passenger_id', $userId)
            ->where('driver_id', $driverId)
            ->count();

        if ($previousRides === 0) {
            return 0;
        }

        return min($previousRides * 0.1, 1.0);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = $this->deg2rad($lat2 - $lat1);
        $dLon = $this->deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($this->deg2rad($lat1)) * cos($this->deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function deg2rad(float $deg): float
    {
        return $deg * (M_PI / 180);
    }
}
