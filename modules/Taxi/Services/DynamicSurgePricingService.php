<?php declare(strict_types=1);

namespace Modules\Taxi\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Taxi\Models\TaxiSurgeZone;

final readonly class DynamicSurgePricingService
{
    private const CACHE_TTL = 300;
    private const SURGE_DECAY_RATE = 0.1;
    private const MIN_SURGE_MULTIPLIER = 1.0;
    private const MAX_SURGE_MULTIPLIER = 5.0;

    public function __construct(
        private AuditService $audit,
    ) {}

    public function calculateSurgeMultiplier(float $latitude, float $longitude, string $correlationId): array
    {
        $cacheKey = "taxi:surge:{$latitude}:{$longitude}";
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $surgeZone = $this->findSurgeZone($latitude, $longitude);
        $demandFactor = $this->calculateDemandFactor($latitude, $longitude);
        $supplyFactor = $this->calculateSupplyFactor($latitude, $longitude);
        $timeFactor = $this->calculateTimeFactor();
        $weatherFactor = $this->calculateWeatherFactor($latitude, $longitude);
        $eventFactor = $this->calculateEventFactor($latitude, $longitude);

        $baseMultiplier = $surgeZone ? $surgeZone->base_multiplier : 1.0;
        $calculatedMultiplier = $baseMultiplier * $demandFactor * $timeFactor * $weatherFactor * $eventFactor / max($supplyFactor, 0.1);

        $multiplier = min(max($calculatedMultiplier, self::MIN_SURGE_MULTIPLIER), self::MAX_SURGE_MULTIPLIER);

        $result = [
            'multiplier' => round($multiplier, 2),
            'zone_id' => $surgeZone?->id,
            'zone_name' => $surgeZone?->name,
            'demand_factor' => round($demandFactor, 2),
            'supply_factor' => round($supplyFactor, 2),
            'time_factor' => round($timeFactor, 2),
            'weather_factor' => round($weatherFactor, 2),
            'event_factor' => round($eventFactor, 2),
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ];

        Redis::setex($cacheKey, self::CACHE_TTL, json_encode($result));

        $this->audit->record('taxi_surge_calculated', 'SurgePricing', null, [], [
            'correlation_id' => $correlationId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'multiplier' => $multiplier,
        ], $correlationId);

        return $result;
    }

    public function triggerSurge(int $zoneId, float $multiplier, string $reason, string $correlationId): void
    {
        $zone = TaxiSurgeZone::findOrFail($zoneId);

        DB::transaction(function () use ($zone, $multiplier, $reason, $correlationId) {
            $zone->update([
                'current_multiplier' => $multiplier,
                'active' => true,
                'triggered_at' => now(),
                'trigger_reason' => $reason,
            ]);

            $this->audit->record('taxi_surge_triggered', 'SurgeZone', $zone->id, [], [
                'correlation_id' => $correlationId,
                'zone_id' => $zone->id,
                'multiplier' => $multiplier,
                'reason' => $reason,
            ], $correlationId);

            Log::channel('audit')->info('Surge triggered', [
                'correlation_id' => $correlationId,
                'zone_id' => $zone->id,
                'multiplier' => $multiplier,
            ]);
        });
    }

    public function decaySurge(int $zoneId, string $correlationId): void
    {
        $zone = TaxiSurgeZone::findOrFail($zoneId);

        DB::transaction(function () use ($zone, $correlationId) {
            $newMultiplier = max(
                $zone->current_multiplier * (1 - self::SURGE_DECAY_RATE),
                self::MIN_SURGE_MULTIPLIER
            );

            $zone->update([
                'current_multiplier' => $newMultiplier,
            ]);

            if ($newMultiplier <= self::MIN_SURGE_MULTIPLIER) {
                $zone->update([
                    'active' => false,
                    'deactivated_at' => now(),
                ]);
            }

            $this->audit->record('taxi_surge_decayed', 'SurgeZone', $zone->id, [], [
                'correlation_id' => $correlationId,
                'zone_id' => $zone->id,
                'new_multiplier' => $newMultiplier,
            ], $correlationId);
        });
    }

    private function findSurgeZone(float $latitude, float $longitude): ?TaxiSurgeZone
    {
        return TaxiSurgeZone::where('active', true)
            ->whereRaw('ST_Contains(geometry, ST_GeomFromText(POINT(?, ?), 4326))', [$longitude, $latitude])
            ->first();
    }

    private function calculateDemandFactor(float $latitude, float $longitude): float
    {
        $recentRides = DB::table('taxi_rides')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        $baselineDemand = 50;
        return min($recentRides / $baselineDemand, 3.0);
    }

    private function calculateSupplyFactor(float $latitude, float $longitude): float
    {
        $availableDrivers = DB::table('taxi_drivers')
            ->where('status', 'available')
            ->where('is_online', true)
            ->count();

        $baselineSupply = 30;
        return max($availableDrivers / $baselineSupply, 0.2);
    }

    private function calculateTimeFactor(): float
    {
        $hour = now()->hour;

        return match (true) {
            $hour >= 7 && $hour < 9 => 2.0,
            $hour >= 17 && $hour < 20 => 2.5,
            $hour >= 22 || $hour < 2 => 1.8,
            $hour >= 2 && $hour < 6 => 0.8,
            default => 1.0,
        };
    }

    private function calculateWeatherFactor(float $latitude, float $longitude): float
    {
        $weather = $this->getWeatherData($latitude, $longitude);

        return match ($weather['condition'] ?? 'clear') {
            'rain', 'snow' => 1.5,
            'storm' => 2.0,
            'fog' => 1.3,
            default => 1.0,
        };
    }

    private function calculateEventFactor(float $latitude, float $longitude): float
    {
        $nearbyEvents = DB::table('events')
            ->where('start_time', '>=', now()->subHours(2))
            ->where('start_time', '<=', now()->addHours(2))
            ->whereRaw('ST_Distance_Sphere(location, POINT(?, ?)) < 5000', [$longitude, $latitude])
            ->count();

        return 1.0 + ($nearbyEvents * 0.3);
    }

    private function getWeatherData(float $latitude, float $longitude): array
    {
        $cacheKey = "weather:{$latitude}:{$longitude}";
        $cached = Redis::get($cacheKey);

        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $weather = [
            'condition' => 'clear',
            'temperature' => 20,
        ];

        Redis::setex($cacheKey, 1800, json_encode($weather));

        return $weather;
    }
}
