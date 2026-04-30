<?php

declare(strict_types=1);

namespace App\Services\Geo;

use Psr\Log\LoggerInterface;

use App\Services\Geo\Providers\OSMProvider;
use App\Services\Geo\Providers\YandexMapsProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use App\Traits\WithAuditLogging;
use App\Services\Security\AuditService;

/**
 * Unified Geolocation Service Facade
 *
 * Provides single entry point for all geolocation operations
 * with automatic fallback between providers (Yandex → OSM → Haversine)
 * and circuit breaker pattern for external APIs.
 */
final readonly class GeoService
{
    use WithAuditLogging;

    private const CACHE_TTL_SECONDS = 300;

    private const CIRCUIT_BREAKER_KEY = 'geo:circuit_breaker:';

    private const CIRCUIT_BREAKER_TTL = 300; // 5 minutes

    private const FAILURE_THRESHOLD = 5;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConfigRepository $config,
        private readonly Repository $cache,
        private readonly LogManager $log,
        private readonly YandexMapsProvider $yandexProvider,
        private readonly OSMProvider $osmProvider,
        private readonly RedisFactory $redis,
        private readonly DatabaseManager $db,
        private readonly AuditService $audit,
    ) {}

    /**
     * Calculate distance between two points in kilometers
     * Uses primary provider with automatic fallback
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $cacheKey = "geo:distance:{$lat1}:{$lon1}:{$lat2}:{$lon2}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($lat1, $lon1, $lat2, $lon2): float {
            $provider = $this->getAvailableProvider();

            try {
                return $provider->calculateDistance($lat1, $lon1, $lat2, $lon2);
            } catch (\Throwable $e) {
                $this->recordFailure($provider);

                return $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
            }
        });
    }

    /**
     * Calculate route between two points
     *
     * @return array{distance_km: float, duration_min: int, polyline: string}
     */
    public function calculateRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $cacheKey = "geo:route:{$lat1}:{$lon1}:{$lat2}:{$lon2}";

        return $this->cache->remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($lat1, $lon1, $lat2, $lon2): array {
            $provider = $this->getAvailableProvider();

            try {
                return $provider->calculateRoute($lat1, $lon1, $lat2, $lon2);
            } catch (\Throwable $e) {
                $this->recordFailure($provider);

                return $this->haversineRoute($lat1, $lon1, $lat2, $lon2);
            }
        });
    }

    /**
     * Geocode address to coordinates
     *
     * @return array{lat: float, lon: float}|null
     */
    public function geocode(string $address): ?array
    {
        $cacheKey = 'geo:geocode:'.md5($address);

        return $this->cache->remember($cacheKey, 3600, function () use ($address): ?array {
            $provider = $this->getAvailableProvider();

            try {
                return $provider->geocode($address);
            } catch (\Throwable $e) {
                $this->recordFailure($provider);
                $this->logger->channel('geo')->error('All geocoding providers failed', [
                    'address' => $address,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Reverse geocode coordinates to address
     */
    public function reverseGeocode(float $lat, float $lon): ?string
    {
        $cacheKey = "geo:reverse:{$lat}:{$lon}";

        return $this->cache->remember($cacheKey, 3600, function () use ($lat, $lon): ?string {
            $provider = $this->getAvailableProvider();

            try {
                return $provider->reverseGeocode($lat, $lon);
            } catch (\Throwable $e) {
                $this->recordFailure($provider);

                return null;
            }
        });
    }

    /**
     * Find nearby items within radius (PostGIS spatial query)
     */
    public function findNearby(float $lat, float $lon, float $radiusKm, string $table, array $conditions = []): array
    {
        $query = $this->db->table($table)
            ->selectRaw('*, ST_Distance_Sphere(
                ST_MakePoint(lon, lat),
                ST_MakePoint(?, ?)
            ) / 1000 as distance_km', [$lon, $lat])
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km');

        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        return $query->get()->toArray();
    }

    /**
     * Get geohash for coordinates (privacy-preserving location)
     */
    public function getGeohash(float $lat, float $lon, int $precision = 7): string
    {
        // Simple geohash implementation
        // For production, use library like 'geohash-php'
        $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
        $latRange = [-90.0, 90.0];
        $lonRange = [-180.0, 180.0];
        $geohash = '';
        $bits = 0;
        $bit = 0;
        $evenBit = true;

        while (strlen($geohash) < $precision) {
            if ($evenBit) {
                $mid = ($lonRange[0] + $lonRange[1]) / 2;
                if ($lon > $mid) {
                    $bit |= 1;
                    $lonRange[0] = $mid;
                } else {
                    $lonRange[1] = $mid;
                }
            } else {
                $mid = ($latRange[0] + $latRange[1]) / 2;
                if ($lat > $mid) {
                    $bit |= 1;
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }

            $evenBit = ! $evenBit;

            if ($bits < 4) {
                $bits++;
            } else {
                $geohash .= $base32[$bit];
                $bits = 0;
                $bit = 0;
            }
        }

        return $geohash;
    }

    /**
     * Anonymize coordinates for privacy compliance (152-ФЗ)
     * Rounds to specified precision
     */
    public function anonymizeCoordinates(float $lat, float $lon, int $precision = 4): array
    {
        return [
            'lat' => round($lat, $precision),
            'lon' => round($lon, $precision),
        ];
    }

    /**
     * Reset circuit breaker (for admin/monitoring)
     */
    public function resetCircuitBreaker(string $provider): void
    {
        $this->redis->connection()->del(self::CIRCUIT_BREAKER_KEY.$provider);
        $this->redis->connection()->del('geo:failures:'.$provider);
        $this->logger->channel('geo')->$this->logger->info('Geo circuit breaker reset', ['provider' => $provider]);
    }

    /**
     * Get circuit breaker status
     */
    public function getCircuitBreakerStatus(string $provider): array
    {
        return [
            'is_open' => $this->isCircuitOpen($provider),
            'failures' => (int) $this->redis->connection()->get('geo:failures:'.$provider) ?: 0,
            'threshold' => self::FAILURE_THRESHOLD,
            'ttl' => $this->redis->connection()->ttl(self::CIRCUIT_BREAKER_KEY.$provider),
        ];
    }

    /**
     * Get available provider with circuit breaker check
     */
    private function getAvailableProvider(): GeoProviderInterface
    {
        $primaryProvider = $this->config->get('geo.primary_provider', 'yandex');

        if ($primaryProvider === 'yandex' && $this->yandexProvider->isAvailable() && ! $this->isCircuitOpen('yandex')) {
            return $this->yandexProvider;
        }

        return $this->osmProvider;
    }

    /**
     * Check if circuit breaker is open for provider
     */
    private function isCircuitOpen(string $provider): bool
    {
        return (bool) $this->redis->connection()->get(self::CIRCUIT_BREAKER_KEY.$provider);
    }

    /**
     * Record failure for circuit breaker
     */
    private function recordFailure(GeoProviderInterface $provider): void
    {
        $providerName = $provider->getProviderName();
        $key = 'geo:failures:'.$providerName;
        $failures = (int) $this->redis->connection()->incr($key);

        if ($failures === 1) {
            $this->redis->connection()->expire($key, self::CIRCUIT_BREAKER_TTL);
        }

        if ($failures >= self::FAILURE_THRESHOLD) {
            $this->redis->connection()->setex(self::CIRCUIT_BREAKER_KEY.$providerName, self::CIRCUIT_BREAKER_TTL, '1');
            $this->logger->channel('geo')->warning('Geo circuit breaker opened', [
                'provider' => $providerName,
                'failures' => $failures,
            ]);
        }
    }

    /**
     * Haversine distance fallback
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 3);
    }

    /**
     * Haversine route fallback
     */
    private function haversineRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $distance = $this->haversineDistance($lat1, $lon1, $lat2, $lon2);

        return [
            'distance_km' => $distance,
            'duration_min' => (int) ceil($distance / 25 * 60), // 25 km/h average
            'polyline' => '',
        ];
    }
}
