<?php declare(strict_types=1);

namespace Tests\Unit\Services\Geo;

use App\Services\Geo\GeoTelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class GeoTelemetryServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeoTelemetryService $telemetryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telemetryService = app(GeoTelemetryService::class);
        Redis::flushdb();
    }

    public function test_record_geocode_increments_counters(): void
    {
        $this->telemetryService->recordGeocode('yandex', true, 150.5);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(1, $stats['geocoding']['total']);
        $this->assertEquals(1, $stats['geocoding']['success']);
        $this->assertEquals(0, $stats['geocoding']['failure']);
    }

    public function test_record_geocode_failure(): void
    {
        $this->telemetryService->recordGeocode('yandex', false, 200.0);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(1, $stats['geocoding']['total']);
        $this->assertEquals(0, $stats['geocoding']['success']);
        $this->assertEquals(1, $stats['geocoding']['failure']);
    }

    public function test_record_route_increments_counters(): void
    {
        $this->telemetryService->recordRoute('osm', true, 300.0, 5.5);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(1, $stats['routing']['total']);
        $this->assertEquals(1, $stats['routing']['success']);
    }

    public function test_record_cache_hit(): void
    {
        $this->telemetryService->recordCacheHit('geocode', true);
        $this->telemetryService->recordCacheHit('geocode', false);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(2, $stats['cache']['geocode_total']);
        $this->assertEquals(1, $stats['cache']['geocode_hit']);
    }

    public function test_record_tracking_update(): void
    {
        $this->telemetryService->recordTrackingUpdate('courier');
        $this->telemetryService->recordTrackingUpdate('doctor');
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(1, $stats['tracking']['courier_updates']);
        $this->assertEquals(1, $stats['tracking']['doctor_updates']);
    }

    public function test_record_circuit_breaker_event(): void
    {
        $this->telemetryService->recordCircuitBreaker('yandex', 'open');
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(1, $stats['circuit_breaker']['yandex_opens']);
    }

    public function test_get_prometheus_metrics_returns_string(): void
    {
        $this->telemetryService->recordGeocode('yandex', true, 150.5);
        
        $metrics = $this->telemetryService->getPrometheusMetrics();
        
        $this->assertIsString($metrics);
        $this->assertStringContainsString('geo_geocode_total', $metrics);
        $this->assertStringContainsString('# HELP', $metrics);
        $this->assertStringContainsString('# TYPE', $metrics);
    }

    public function test_get_statistics_returns_array(): void
    {
        $this->telemetryService->recordGeocode('yandex', true, 150.5);
        $this->telemetryService->recordRoute('osm', true, 300.0, 5.5);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('geocoding', $stats);
        $this->assertArrayHasKey('routing', $stats);
        $this->assertArrayHasKey('cache', $stats);
        $this->assertArrayHasKey('tracking', $stats);
        $this->assertArrayHasKey('circuit_breaker', $stats);
    }

    public function test_get_success_rate(): void
    {
        $this->telemetryService->recordGeocode('yandex', true, 150.5);
        $this->telemetryService->recordGeocode('yandex', true, 160.0);
        $this->telemetryService->recordGeocode('yandex', false, 200.0);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(0.666, round($stats['geocoding']['success_rate'], 3));
    }

    public function test_get_average_latency(): void
    {
        $this->telemetryService->recordGeocode('yandex', true, 150.0);
        $this->telemetryService->recordGeocode('yandex', true, 200.0);
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(175.0, $stats['geocoding']['avg_latency_ms']);
    }

    public function test_reset_metrics_clears_all(): void
    {
        $this->telemetryService->recordGeocode('yandex', true, 150.5);
        $this->telemetryService->resetMetrics();
        
        $stats = $this->telemetryService->getStatistics();
        
        $this->assertEquals(0, $stats['geocoding']['total']);
        $this->assertEquals(0, $stats['routing']['total']);
    }
}
