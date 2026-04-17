<?php declare(strict_types=1);

namespace App\Services\Geo\Providers;

use App\Services\Geo\GeoProviderInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Log\LogManager;

/**
 * Yandex Maps Provider
 * Primary provider for Russia/CIS region
 */
final readonly class YandexMapsProvider implements GeoProviderInterface
{
    private const ROUTER_URL = 'https://api-maps.yandex.ru/services/route/v2';
    private const GEOCODE_URL = 'https://geocode-maps.yandex.ru/1.x';

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
    ) {}

    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $route = $this->calculateRoute($lat1, $lon1, $lat2, $lon2);
        return $route['distance_km'];
    }

    public function calculateRoute(float $lat1, float $lon1, float $lat2, float $lon2): array
    {
        $apiKey = $this->config->get('geo.providers.yandex.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('Yandex Maps API key not configured');
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get(self::ROUTER_URL, [
                    'waypoints' => "{$lat1},{$lon1}|{$lat2},{$lon2}",
                    'apikey'    => $apiKey,
                    'mode'      => 'driving',
                    'lang'      => 'ru_RU',
                ]);

            $route = $response->json('route.0', null);

            if (empty($route)) {
                throw new \RuntimeException('Invalid Yandex response');
            }

            return [
                'distance_km'  => round((float) ($route['distance']['value'] ?? 0) / 1000, 2),
                'duration_min' => (int) round((float) ($route['duration']['value'] ?? 0) / 60),
                'polyline'     => (string) ($route['geometry'] ?? ''),
            ];
        } catch (\Throwable $e) {
            $this->logger->channel('geo')->error('Yandex route calculation failed', [
                'error' => $e->getMessage(),
                'from' => "{$lat1},{$lon1}",
                'to' => "{$lat2},{$lon2}",
            ]);
            throw $e;
        }
    }

    public function geocode(string $address): ?array
    {
        $apiKey = $this->config->get('geo.providers.yandex.api_key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get(self::GEOCODE_URL, [
                    'geocode' => $address,
                    'apikey'  => $apiKey,
                    'format'  => 'json',
                    'results' => 1,
                ]);

            $pos = $response->json(
                'response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos'
            );

            if (empty($pos)) {
                return null;
            }

            [$lon, $lat] = explode(' ', (string) $pos);

            return ['lat' => (float) $lat, 'lon' => (float) $lon];
        } catch (\Throwable $e) {
            $this->logger->channel('geo')->error('Yandex geocoding failed', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function reverseGeocode(float $lat, float $lon): ?string
    {
        $apiKey = $this->config->get('geo.providers.yandex.api_key');

        if (empty($apiKey)) {
            return null;
        }

        try {
            $response = Http::timeout(5)
                ->retry(2, 200)
                ->get(self::GEOCODE_URL, [
                    'geocode' => "{$lon},{$lat}",
                    'apikey'  => $apiKey,
                    'format'  => 'json',
                    'results' => 1,
                ]);

            $address = $response->json(
                'response.GeoObjectCollection.featureMember.0.GeoObject.metaDataProperty.GeocoderMetaData.text'
            );

            return $address ?: null;
        } catch (\Throwable $e) {
            $this->logger->channel('geo')->error('Yandex reverse geocoding failed', [
                'lat' => $lat,
                'lon' => $lon,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getProviderName(): string
    {
        return 'yandex';
    }

    public function isAvailable(): bool
    {
        $apiKey = $this->config->get('geo.providers.yandex.api_key');
        return !empty($apiKey);
    }
}
