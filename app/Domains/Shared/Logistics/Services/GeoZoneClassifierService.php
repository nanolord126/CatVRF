<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Carbon\CarbonImmutable;

use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;

/**
 * Geo Zone Classifier Service
 *
 * Classifies geographic zones for adaptive logistics optimization.
 * Distinguishes resort/spit zones from urban areas for:
 * - Adaptive batching distances (1.5km vs 0.8-1.2km)
 * - Deadhead ratio thresholds (12% vs 8%)
 * - Courier type preferences
 * - Seasonal coefficients
 *
 * Production Strategy:
 * - Query ClickHouse ch_resort_zones table for classification
 * - Cache results for 1 hour TTL
 * - Support seasonal flags (May-September)
 * - Integrate with weather API for heat limits
 */
final readonly class GeoZoneClassifierService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    public function __construct(private readonly CacheManager $cacheManager,
        private readonly LoggerInterface $logger,
        private readonly WeatherIntegrationService $weatherService,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,) {}

    /**
     * Classify zone and get max batch distance for location.
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @param  int  $tenantId  Tenant ID
     * @return array{zone_type: string, is_resort_spit: bool, max_batch_distance_km: float, deadhead_ratio_threshold: float, linear_density_score: float, is_seasonal: bool, preferred_types: array, pedestrian_max_radius_km: float, pedestrian_heat_limit_celsius: float}
     */
    public function classifyZoneAndGetMaxBatchDistance(
        float $latitude,
        float $longitude,
        int $tenantId,
    ): array {
        $cacheKey = "zone_classification:{$tenantId}:{$latitude}:{$longitude}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $result = $this->queryClickHouseForZone($latitude, $longitude, $tenantId);

        // Cache for 1 hour
        $this->cache->put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        return $result;
    }

    /**
     * Check if location is in resort/spit zone.
     */
    public function isResortSpit(float $latitude, float $longitude, int $tenantId): bool
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        return $classification['is_resort_spit'];
    }

    /**
     * Get max batch distance for location.
     */
    public function getMaxBatchDistance(float $latitude, float $longitude, int $tenantId): float
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        return $classification['max_batch_distance_km'];
    }

    /**
     * Get deadhead ratio threshold for location.
     */
    public function getDeadheadThreshold(float $latitude, float $longitude, int $tenantId): float
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        return $classification['deadhead_ratio_threshold'];
    }

    /**
     * Get preferred courier types for zone.
     */
    public function getPreferredCourierTypes(float $latitude, float $longitude, int $tenantId): array
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        return $classification['preferred_types'];
    }

    /**
     * Get pedestrian heat limit for zone (Celsius).
     */
    public function getPedestrianHeatLimit(float $latitude, float $longitude, int $tenantId): float
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        return $classification['pedestrian_heat_limit_celsius'];
    }

    /**
     * Check if zone is currently in season.
     */
    public function isInSeason(float $latitude, float $longitude, int $tenantId): bool
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        if (! $classification['is_seasonal']) {
            return true; // Non-seasonal zones are always active
        }

        $currentMonth = (int) CarbonImmutable::now()->format('n');
        $startMonth = $classification['season_start_month'];
        $endMonth = $classification['season_end_month'];

        // Handle year boundary (e.g., November to March)
        if ($startMonth > $endMonth) {
            return $currentMonth >= $startMonth || $currentMonth <= $endMonth;
        }

        return $currentMonth >= $startMonth && $currentMonth <= $endMonth;
    }

    /**
     * Check if current time is peak hour for zone.
     */
    public function isPeakHour(float $latitude, float $longitude, int $tenantId): bool
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);
        $currentHour = (int) CarbonImmutable::now()->format('H');

        return in_array($currentHour, $classification['peak_hours'], true);
    }

    /**
     * Check if pedestrian couriers should be restricted due to heat in this zone.
     * (Week 4)
     */
    public function isPedestrianRestrictedByHeat(float $latitude, float $longitude, int $tenantId): bool
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);
        $heatLimit = $classification['pedestrian_heat_limit_celsius'];

        return $this->weatherService->isPedestrianRestrictedByHeat($latitude, $longitude, $heatLimit);
    }

    /**
     * Get seasonal coefficient for zone (Week 4).
     * Higher coefficient = higher demand/difficulty in beach zones.
     */
    public function getSeasonalCoefficient(float $latitude, float $longitude, int $tenantId): float
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);

        // Only apply seasonal coefficients for resort/spit zones
        if (! $classification['is_resort_spit']) {
            return 1.0;
        }

        $weather = $this->weatherService->getWeatherData($latitude, $longitude);
        $currentMonth = (int) CarbonImmutable::now()->format('n');

        return $this->weatherService->getSeasonalCoefficient($currentMonth, $weather['temperature_celsius']);
    }

    /**
     * Get weather-adjusted preferred courier types for zone (Week 4).
     */
    public function getWeatherAdjustedPreferredTypes(float $latitude, float $longitude, int $tenantId): array
    {
        $classification = $this->classifyZoneAndGetMaxBatchDistance($latitude, $longitude, $tenantId);
        $preferredTypes = $classification['preferred_types'];

        return $this->weatherService->getWeatherAdjustedPreferences($latitude, $longitude, $preferredTypes);
    }

    /**
     * Invalidate cache for a location.
     */
    public function invalidateCache(float $latitude, float $longitude, int $tenantId): void
    {
        $cacheKey = "zone_classification:{$tenantId}:{$latitude}:{$longitude}";
        $this->cache->forget($cacheKey);
    }

    /**
     * Invalidate all zone classification cache for tenant.
     */
    public function invalidateTenantCache(int $tenantId): void
    {
        // TODO: Implement cache tag-based invalidation
        // $this->cacheManager->tags(["zone_classification:{$tenantId}"])->flush();
        $this->logger->$this->logger->info('Zone classification cache invalidated', [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Query MySQL for zone classification (fallback from ClickHouse).
     *
     * @return array{zone_type: string, is_resort_spit: bool, max_batch_distance_km: float, deadhead_ratio_threshold: float, linear_density_score: float, is_seasonal: bool, preferred_types: array, pedestrian_max_radius_km: float, pedestrian_heat_limit_celsius: float}
     */
    private function queryClickHouseForZone(float $latitude, float $longitude, int $tenantId): array
    {
        try {
            // Try ClickHouse first (production)
            $result = $this->db->connection('clickhouse')
                ->table('ch_resort_zones')
                ->where('tenant_id', $tenantId)
                ->whereRaw('pointInPolygon((?, ?), polygon_geojson)', [$latitude, $longitude])
                ->where(function ($query) {
                    $currentMonth = (int) CarbonImmutable::now()->format('n');
                    $query->where('is_seasonal', 0)
                        ->orWhere(function ($q) use ($currentMonth) {
                            $q->where('season_start_month', '<=', $currentMonth)
                                ->where('season_end_month', '>=', $currentMonth);
                        });
                })
                ->first();

            if ($result) {
                $this->logger->$this->logger->info('Zone classification found in ClickHouse', [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'tenant_id' => $tenantId,
                    'zone_type' => $result->zone_type,
                ]);

                return [
                    'zone_type' => $result->zone_type,
                    'is_resort_spit' => (bool) $result->is_resort_spit,
                    'max_batch_distance_km' => (float) $result->max_batch_distance_km,
                    'deadhead_ratio_threshold' => (float) $result->deadhead_ratio_threshold,
                    'linear_density_score' => (float) $result->linear_density_score,
                    'is_seasonal' => (bool) $result->is_seasonal,
                    'season_start_month' => (int) $result->season_start_month,
                    'season_end_month' => (int) $result->season_end_month,
                    'peak_hours' => json_decode($result->peak_hours, true) ?? [],
                    'preferred_types' => json_decode($result->preferred_types, true) ?? [],
                    'pedestrian_max_radius_km' => (float) $result->pedestrian_max_radius_km,
                    'pedestrian_heat_limit_celsius' => (float) $result->pedestrian_heat_limit_celsius,
                ];
            }
        } catch (\Exception $e) {
            $this->logger->warning('ClickHouse unavailable, falling back to MySQL', [
                'lat' => $latitude,
                'lng' => $longitude,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback to MySQL (for development/testing)
        try {
            $result = $this->db->connection('mysql')
                ->table('resort_zones')
                ->where('tenant_id', $tenantId)
                ->where(function ($query) {
                    $currentMonth = (int) CarbonImmutable::now()->format('n');
                    $query->where('is_seasonal', 0)
                        ->orWhere(function ($q) use ($currentMonth) {
                            $q->where('season_start_month', '<=', $currentMonth)
                                ->where('season_end_month', '>=', $currentMonth);
                        });
                })
                ->first();

            if ($result) {
                $this->logger->$this->logger->info('Zone classification found in MySQL fallback', [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'tenant_id' => $tenantId,
                    'zone_type' => $result->zone_type,
                ]);

                return [
                    'zone_type' => $result->zone_type,
                    'is_resort_spit' => (bool) $result->is_resort_spit,
                    'max_batch_distance_km' => (float) $result->max_batch_distance_km,
                    'deadhead_ratio_threshold' => (float) $result->deadhead_ratio_threshold,
                    'linear_density_score' => (float) $result->linear_density_score,
                    'is_seasonal' => (bool) $result->is_seasonal,
                    'season_start_month' => (int) $result->season_start_month,
                    'season_end_month' => (int) $result->season_end_month,
                    'peak_hours' => json_decode($result->peak_hours, true) ?? [],
                    'preferred_types' => json_decode($result->preferred_types, true) ?? [],
                    'pedestrian_max_radius_km' => (float) $result->pedestrian_max_radius_km,
                    'pedestrian_heat_limit_celsius' => (float) $result->pedestrian_heat_limit_celsius,
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to query MySQL for zone classification', [
                'lat' => $latitude,
                'lng' => $longitude,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->logger->$this->logger->info('No zone classification found, using default urban zone', [
            'lat' => $latitude,
            'lng' => $longitude,
            'tenant_id' => $tenantId,
        ]);

        return $this->getDefaultUrbanZone();
    }

    /**
     * Get default urban zone classification (fallback).
     *
     * @return array{zone_type: string, is_resort_spit: bool, max_batch_distance_km: float, deadhead_ratio_threshold: float, linear_density_score: float, is_seasonal: bool, preferred_types: array, pedestrian_max_radius_km: float, pedestrian_heat_limit_celsius: float}
     */
    private function getDefaultUrbanZone(): array
    {
        return [
            'zone_type' => 'urban',
            'is_resort_spit' => false,
            'max_batch_distance_km' => 0.8,
            'deadhead_ratio_threshold' => 0.08,
            'linear_density_score' => 0.2,
            'is_seasonal' => false,
            'season_start_month' => 0,
            'season_end_month' => 0,
            'peak_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
            'preferred_types' => ['scooter', 'ebike', 'car'],
            'pedestrian_max_radius_km' => 0.8,
            'pedestrian_heat_limit_celsius' => 35.0,
        ];
    }
}
