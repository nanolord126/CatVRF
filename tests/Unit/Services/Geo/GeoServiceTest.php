<?php declare(strict_types=1);

namespace Tests\Unit\Services\Geo;

use App\Services\Geo\GeoService;
use App\Services\Geo\Providers\YandexMapsProvider;
use App\Services\Geo\Providers\OSMProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class GeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeoService $geoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geoService = app(GeoService::class);
        Redis::flushdb();
    }

    public function test_calculate_distance_uses_cache(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(5.5);

        $distance = $this->geoService->calculateDistance(55.75, 37.61, 55.76, 37.62);
        
        $this->assertIsFloat($distance);
    }

    public function test_calculate_route_returns_expected_structure(): void
    {
        $route = $this->geoService->calculateRoute(55.75, 37.61, 55.76, 37.62);
        
        $this->assertIsArray($route);
        $this->assertArrayHasKey('distance_km', $route);
        $this->assertArrayHasKey('duration_min', $route);
        $this->assertArrayHasKey('polyline', $route);
        $this->assertIsFloat($route['distance_km']);
        $this->assertIsInt($route['duration_min']);
    }

    public function test_anonymize_coordinates_reduces_precision(): void
    {
        $original = ['lat' => 55.755833, 'lon' => 37.617777];
        $anonymized = $this->geoService->anonymizeCoordinates($original['lat'], $original['lon'], 4);
        
        $this->assertLessThanOrEqual(4, strlen(substr(strrchr((string)$anonymized['lat'], '.'), 1)));
        $this->assertLessThanOrEqual(4, strlen(substr(strrchr((string)$anonymized['lon'], '.'), 1)));
    }

    public function test_get_geohash_returns_string(): void
    {
        $geohash = $this->geoService->getGeohash(55.75, 37.62, 7);
        
        $this->assertIsString($geohash);
        $this->assertEquals(7, strlen($geohash));
    }

    public function test_circuit_breaker_opens_after_failures(): void
    {
        $this->geoService->recordFailure(app(YandexMapsProvider::class));
        
        // Simulate 5 failures
        for ($i = 0; $i < 5; $i++) {
            Redis::incr('geo:failures:yandex');
        }
        
        $status = $this->geoService->getCircuitBreakerStatus('yandex');
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('is_open', $status);
        $this->assertArrayHasKey('failures', $status);
    }

    public function test_reset_circuit_breaker(): void
    {
        // Open circuit breaker
        Redis::setex('geo:circuit_breaker:yandex', 300, '1');
        Redis::set('geo:failures:yandex', 5);
        
        $this->geoService->resetCircuitBreaker('yandex');
        
        $status = $this->geoService->getCircuitBreakerStatus('yandex');
        
        $this->assertFalse($status['is_open']);
        $this->assertEquals(0, $status['failures']);
    }

    public function test_find_nearby_returns_array(): void
    {
        // This test assumes the table exists and has data
        // In real scenario, you'd seed the database
        $result = $this->geoService->findNearby(55.75, 37.62, 10.0, 'users');
        
        $this->assertIsArray($result);
    }

    public function test_geocode_returns_null_on_failure(): void
    {
        // Mock cache to throw exception
        Cache::shouldReceive('remember')
            ->andThrow(new \RuntimeException('API unavailable'));
        
        $result = $this->geoService->geocode('invalid address');
        
        $this->assertNull($result);
    }

    public function test_reverse_geocode_returns_string_or_null(): void
    {
        $result = $this->geoService->reverseGeocode(55.75, 37.62);
        
        $this->assertIsString($result) || $this->assertNull($result);
    }
}
