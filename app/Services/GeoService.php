<?php declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Cache\CacheManager;




final readonly class GeoService
{
    public function __construct(
        private RateLimiterService $rateLimiterService,
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
    ) {}

    public function getDistance(array $from, array $to, string $correlationId = ''): float
    {
        // from и to: ['lat' => ..., 'lon' => ...]
        $cacheKey = "distance:{$from['lat']}:{$from['lon']}:{$to['lat']}:{$to['lon']}";

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        // Используем формулу Haversine для простого расчета
        $distance = $this->haversineDistance(
            $from['lat'],
            $from['lon'],
            $to['lat'],
            $to['lon'],
        );

        $this->cache->put($cacheKey, $distance, 86400); // 1 день

        return $distance;
    }

    public function getNearbyItems(array $geoPoint, int $radiusKm, string $entityType = 'products'): array
    {
        $query = $this->db->table($entityType)
            ->selectRaw('*, ST_Distance(geo_point, ?) / 1000 as distance_km', [$geoPoint])
            ->havingRaw('distance_km <= ?', [$radiusKm])
            ->orderBy('distance_km');

        return $query->get()->toArray();
    }

    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // км

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
