<?php

declare(strict_types=1);

namespace App\Domains\Logistics\Services;

use Psr\Log\LoggerInterface;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;

/**
 * Weather Integration Service
 *
 * Integrates weather data for resort/beach zone optimization.
 * Applies seasonal coefficients and heat limits for pedestrian couriers.
 *
 * Production Strategy:
 * - Fetch weather data from external API (OpenWeatherMap, Yandex.Weather, etc.)
 * - Cache weather data for 30 minutes
 * - Apply seasonal coefficients (May-September) for beach zones
 * - Enforce heat limits for pedestrian couriers (35°C default)
 * - Support humidity and wind speed adjustments
 */
final readonly class WeatherIntegrationService
{
    private const CACHE_TTL_SECONDS = 1800; // 30 minutes

    public function __construct(private readonly LoggerInterface $logger,
        private readonly LogManager $log,
        private readonly Repository $config,
        private readonly CacheManager $cache,
        private readonly HttpFactory $http,) {}

    /**
     * Get weather data for location.
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @return array{temperature_celsius: float, humidity_percent: float, wind_speed_kmh: float, weather_condition: string, is_heat_wave: bool}
     */
    public function getWeatherData(float $latitude, float $longitude): array
    {
        $cacheKey = "weather:{$latitude}:{$longitude}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $apiKey = $this->config->get('services.openweathermap.api_key');
        $baseUrl = $this->config->get('services.openweathermap.base_url');
        $units = $this->config->get('services.openweathermap.units', 'metric');

        if (empty($apiKey)) {
            $this->log->warning('OpenWeatherMap API key not configured, using placeholder data', [
                'lat' => $latitude,
                'lng' => $longitude,
            ]);

            // Fallback to placeholder data
            $weatherData = [
                'temperature_celsius' => 28.0,
                'humidity_percent' => 65.0,
                'wind_speed_kmh' => 15.0,
                'weather_condition' => 'sunny',
                'is_heat_wave' => false,
            ];

            $this->cache->put($cacheKey, $weatherData, self::CACHE_TTL_SECONDS);

            return $weatherData;
        }

        try {
            $response = $this->http->timeout(10)->get("{$baseUrl}/weather", [
                'lat' => $latitude,
                'lon' => $longitude,
                'appid' => $apiKey,
                'units' => $units,
            ]);

            if ($response->failed()) {
                $this->log->error('OpenWeatherMap API call failed', [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                // Fallback to placeholder data
                $weatherData = [
                    'temperature_celsius' => 28.0,
                    'humidity_percent' => 65.0,
                    'wind_speed_kmh' => 15.0,
                    'weather_condition' => 'sunny',
                    'is_heat_wave' => false,
                ];

                $this->cache->put($cacheKey, $weatherData, 300); // Shorter cache on error

                return $weatherData;
            }

            $data = $response->json();

            $weatherData = [
                'temperature_celsius' => $data['main']['temp'] ?? 28.0,
                'humidity_percent' => $data['main']['humidity'] ?? 65.0,
                'wind_speed_kmh' => ($data['wind']['speed'] ?? 4.17) * 3.6, // Convert m/s to km/h
                'weather_condition' => $data['weather'][0]['main'] ?? 'clear',
                'is_heat_wave' => ($data['main']['temp'] ?? 28.0) >= 35.0,
            ];

            $this->cache->put($cacheKey, $weatherData, $this->config->get('services.openweathermap.cache_ttl', self::CACHE_TTL_SECONDS));

            $this->log->$this->logger->info('Weather data fetched from OpenWeatherMap', [
                'lat' => $latitude,
                'lng' => $longitude,
                'temperature_celsius' => $weatherData['temperature_celsius'],
                'weather_condition' => $weatherData['weather_condition'],
            ]);

            return $weatherData;
        } catch (\Exception $e) {
            $this->log->error('Failed to fetch weather data', [
                'lat' => $latitude,
                'lng' => $longitude,
                'error' => $e->getMessage(),
            ]);

            // Fallback to placeholder data
            $weatherData = [
                'temperature_celsius' => 28.0,
                'humidity_percent' => 65.0,
                'wind_speed_kmh' => 15.0,
                'weather_condition' => 'sunny',
                'is_heat_wave' => false,
            ];

            $this->cache->put($cacheKey, $weatherData, 300); // Shorter cache on error

            return $weatherData;
        }
    }

    /**
     * Get seasonal coefficient for beach zones.
     * Higher coefficient = higher demand/difficulty.
     *
     * @param  int  $month  Month (1-12)
     * @param  float  $temperatureCelsius  Temperature in Celsius
     * @return float Seasonal coefficient (0.5-2.0)
     */
    public function getSeasonalCoefficient(int $month, float $temperatureCelsius): float
    {
        // Base seasonal coefficient (May-September = peak season)
        $baseCoefficient = match (true) {
            $month >= 5 && $month <= 9 => 1.5, // Peak season (May-September)
            $month === 4 || $month === 10 => 1.2, // Shoulder season
            default => 0.8, // Off-season
        };

        // Temperature adjustment
        $temperatureAdjustment = match (true) {
            $temperatureCelsius >= 35 => 0.3, // Heat wave penalty
            $temperatureCelsius >= 30 => 0.1, // Hot weather
            $temperatureCelsius <= 15 => -0.2, // Cold weather (less beach activity)
            default => 0.0,
        };

        $coefficient = $baseCoefficient + $temperatureAdjustment;

        return max(0.5, min(2.0, $coefficient));
    }

    /**
     * Check if pedestrian couriers should be restricted due to heat.
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @param  float  $heatLimitCelsius  Heat limit in Celsius (default 35°C)
     * @return bool True if pedestrian couriers should be restricted
     */
    public function isPedestrianRestrictedByHeat(float $latitude, float $longitude, float $heatLimitCelsius = 35.0): bool
    {
        $weather = $this->getWeatherData($latitude, $longitude);

        $isRestricted = $weather['temperature_celsius'] >= $heatLimitCelsius;

        if ($isRestricted) {
            $this->log->warning('Pedestrian couriers restricted due to heat', [
                'lat' => $latitude,
                'lng' => $longitude,
                'temperature_celsius' => $weather['temperature_celsius'],
                'heat_limit_celsius' => $heatLimitCelsius,
            ]);
        }

        return $isRestricted;
    }

    /**
     * Get adjusted courier preferences based on weather.
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @param  array  $preferredTypes  Preferred courier types
     * @return array Adjusted preferred courier types
     */
    public function getWeatherAdjustedPreferences(float $latitude, float $longitude, array $preferredTypes): array
    {
        $weather = $this->getWeatherData($latitude, $longitude);

        // If heat wave, restrict pedestrians
        if ($weather['temperature_celsius'] >= 35.0) {
            return array_filter($preferredTypes, fn ($type) => $type !== 'pedestrian');
        }

        // If rain/snow, prefer cars over scooters/ebikes
        if (in_array($weather['weather_condition'], ['rain', 'snow', 'storm'], true)) {
            if (in_array('car', $preferredTypes, true)) {
                return ['car', 'taxi'];
            }
        }

        return $preferredTypes;
    }

    /**
     * Get weather-adjusted delivery time multiplier.
     *
     * @param  float  $latitude  Latitude
     * @param  float  $longitude  Longitude
     * @return float Time multiplier (1.0 = normal, >1.0 = slower)
     */
    public function getDeliveryTimeMultiplier(float $latitude, float $longitude): float
    {
        $weather = $this->getWeatherData($latitude, $longitude);

        $multiplier = 1.0;

        // Rain/snow penalty
        if (in_array($weather['weather_condition'], ['rain', 'snow', 'storm'], true)) {
            $multiplier += 0.3;
        }

        // High wind penalty
        if ($weather['wind_speed_kmh'] > 30) {
            $multiplier += 0.2;
        }

        // Extreme heat penalty (for pedestrians/scooters)
        if ($weather['temperature_celsius'] > 35) {
            $multiplier += 0.15;
        }

        return min(2.0, $multiplier); // Cap at 2x
    }

    /**
     * Invalidate weather cache for location.
     */
    public function invalidateCache(float $latitude, float $longitude): void
    {
        $cacheKey = "weather:{$latitude}:{$longitude}";
        $this->cache->forget($cacheKey);
    }
}
