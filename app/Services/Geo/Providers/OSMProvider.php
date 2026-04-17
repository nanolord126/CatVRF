<?php declare(strict_types=1);

namespace App\Services\Geo\Providers;

use App\Services\Geo\GeoProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Log\LogManager;

/**
 * OpenStreetMap (OSM) Provider
 * Fallback provider using Nominatim and OSRM
 */
final readonly class OSMProvider implements GeoProviderInterface
{
    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const REVERSE_URL = 'https://nominatim.openstreetmap.org/reverse';
    private const OSRM_URL = 'https://router.project-osrm.org/route/v1/driving';

    public function __construct(
        private readonly LogManager $logger,
    ) {}

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
    }

    public function calculateRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get(self::OSRM_URL . "/{$lon1},{$lat1};{$lon2},{$lat2}", [
                    'overview' => 'full',
                ]);

            $route = $response->json('routes.0', null);

            if (empty($route)) {
                throw new \RuntimeException('Invalid OSRM response');
            }

            return [
                'distance_km'  => round((float) ($route['distance'] ?? 0) / 1000, 2),
                'duration_min' => (int) round((float) ($route['duration'] ?? 0) / 60),
                'polyline'     => (string) ($route['geometry'] ?? ''),
            ];
        } catch (\Throwable $e) {
            $this->logger->channel('geo')->error('OSRM route calculation failed', [
                'error' => $e->getMessage(),
                'from' => "{$lat1},{$lon1}",
                'to' => "{$lat2},{$lon2}",
            ]);

            // Fallback to haversine
            $distance = $this->haversineDistance($lat1, $lon1, $lat2, $lon2);
            return [
                'distance_km'  => $distance,
                'duration_min' => (int) ceil($distance / 25 * 60), // 25 km/h average
                'polyline'     => '',
            ];
        }
    }

    public function geocode(string $address): ?array
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get(self::NOMINATIM_URL, [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

            $result = $response->json('0', null);

            if (empty($result)) {
                return null;
            }

            return [
                'lat' => (float) $result['lat'],
                'lon' => (float) $result['lon'],
            ];
        } catch (\Throwable $e) {
            $this->logger->channel('geo')->error('OSM geocoding failed', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function reverseGeocode(float $lat, float $lon): ?string
    {
        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get(self::REVERSE_URL, [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                ]);

            $address = $response->json('display_name');

            return $address ?: null;
        } catch (\Throwable $e) {
            $this->logger->channel('geo')->error('OSM reverse geocoding failed', [
                'lat' => $lat,
                'lon' => $lon,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getProviderName(): string
    {
        return 'osm';
    }

    public function isAvailable(): bool
    {
        return true; // OSM is always available (no API key required)
    }

    /**
     * Haversine formula for distance calculation
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
}
