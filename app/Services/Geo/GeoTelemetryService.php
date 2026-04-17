<?php declare(strict_types=1);

namespace App\Services\Geo;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Log\LogManager;

/**
 * Geolocation Telemetry Service
 * 
 * Tracks metrics for geolocation infrastructure:
 * - API call latency and success rates
 * - Provider performance
 * - Cache hit rates
 * - Tracking statistics
 */
final readonly class GeoTelemetryService
{
    private const METRICS_PREFIX = 'geo_';
    private const METRICS_TTL = 86400; // 24 hours

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly LogManager $logger,
    ) {}

    /**
     * Record geocoding request
     */
    public function recordGeocode(string $provider, bool $success, float $latencyMs): void
    {
        $pipe = $this->redis->connection()->pipeline();

        $pipe->incr(self::METRICS_PREFIX . 'geocode_total');
        $pipe->incr(self::METRICS_PREFIX . "geocode_provider_{$provider}");
        
        if ($success) {
            $pipe->incr(self::METRICS_PREFIX . 'geocode_success');
        } else {
            $pipe->incr(self::METRICS_PREFIX . 'geocode_failure');
        }

        $pipe->incrby(self::METRICS_PREFIX . 'geocode_latency_ms_total', (int) $latencyMs);
        $pipe->incr(self::METRICS_PREFIX . 'geocode_latency_count');

        $pipe->exec();
    }

    /**
     * Record route calculation
     */
    public function recordRoute(string $provider, bool $success, float $latencyMs, float $distanceKm): void
    {
        $pipe = $this->redis->connection()->pipeline();

        $pipe->incr(self::METRICS_PREFIX . 'route_total');
        $pipe->incr(self::METRICS_PREFIX . "route_provider_{$provider}");
        
        if ($success) {
            $pipe->incr(self::METRICS_PREFIX . 'route_success');
        } else {
            $pipe->incr(self::METRICS_PREFIX . 'route_failure');
        }

        $pipe->incrby(self::METRICS_PREFIX . 'route_latency_ms_total', (int) $latencyMs);
        $pipe->incr(self::METRICS_PREFIX . 'route_latency_count');
        $pipe->incrby(self::METRICS_PREFIX . 'route_distance_km_total', (int) ($distanceKm * 1000));

        $pipe->exec();
    }

    /**
     * Record cache hit/miss
     */
    public function recordCacheHit(string $cacheType, bool $hit): void
    {
        $pipe = $this->redis->connection()->pipeline();

        $pipe->incr(self::METRICS_PREFIX . "cache_{$cacheType}_total");
        
        if ($hit) {
            $pipe->incr(self::METRICS_PREFIX . "cache_{$cacheType}_hit");
        } else {
            $pipe->incr(self::METRICS_PREFIX . "cache_{$cacheType}_miss");
        }

        $pipe->exec();
    }

    /**
     * Record tracking update
     */
    public function recordTrackingUpdate(string $entityType): void
    {
        $this->redis->connection()->incr(self::METRICS_PREFIX . "tracking_{$entityType}_total");
    }

    /**
     * Record circuit breaker event
     */
    public function recordCircuitBreaker(string $provider, string $event): void
    {
        $this->redis->connection()->incr(self::METRICS_PREFIX . "circuit_breaker_{$provider}_{$event}");
    }

    /**
     * Get Prometheus metrics
     */
    public function getPrometheusMetrics(): string
    {
        $lines = [];

        // Geocoding metrics
        $lines[] = $this->formatCounter(
            'geo_geocode_total',
            $this->getCounter('geocode_total'),
            'Total geocoding requests'
        );

        $lines[] = $this->formatCounter(
            'geo_geocode_success',
            $this->getCounter('geocode_success'),
            'Successful geocoding requests'
        );

        $lines[] = $this->formatCounter(
            'geo_geocode_failure',
            $this->getCounter('geocode_failure'),
            'Failed geocoding requests'
        );

        // Route metrics
        $lines[] = $this->formatCounter(
            'geo_route_total',
            $this->getCounter('route_total'),
            'Total route calculations'
        );

        $lines[] = $this->formatGauge(
            'geo_route_avg_distance_km',
            $this->getAverageDistance(),
            'Average route distance in km'
        );

        // Latency metrics
        $lines[] = $this->formatGauge(
            'geo_geocode_avg_latency_ms',
            $this->getAverageLatency('geocode'),
            'Average geocoding latency in ms'
        );

        $lines[] = $this->formatGauge(
            'geo_route_avg_latency_ms',
            $this->getAverageLatency('route'),
            'Average route calculation latency in ms'
        );

        // Cache metrics
        $geocodeCacheHitRate = $this->getCacheHitRate('geocode');
        $lines[] = $this->formatGauge(
            'geo_cache_geocode_hit_rate',
            $geocodeCacheHitRate,
            'Geocoding cache hit rate (0-1)'
        );

        // Tracking metrics
        $lines[] = $this->formatCounter(
            'geo_tracking_courier_total',
            $this->getCounter('tracking_courier_total'),
            'Total courier tracking updates'
        );

        $lines[] = $this->formatCounter(
            'geo_tracking_doctor_total',
            $this->getCounter('tracking_doctor_total'),
            'Total doctor tracking updates'
        );

        return implode("\n", $lines) . "\n";
    }

    /**
     * Get statistics summary
     */
    public function getStatistics(): array
    {
        return [
            'geocoding' => [
                'total' => $this->getCounter('geocode_total'),
                'success' => $this->getCounter('geocode_success'),
                'failure' => $this->getCounter('geocode_failure'),
                'success_rate' => $this->getSuccessRate('geocode'),
                'avg_latency_ms' => $this->getAverageLatency('geocode'),
            ],
            'routing' => [
                'total' => $this->getCounter('route_total'),
                'success' => $this->getCounter('route_success'),
                'failure' => $this->getCounter('route_failure'),
                'success_rate' => $this->getSuccessRate('route'),
                'avg_latency_ms' => $this->getAverageLatency('route'),
                'avg_distance_km' => $this->getAverageDistance(),
            ],
            'cache' => [
                'geocode_hit_rate' => $this->getCacheHitRate('geocode'),
                'route_hit_rate' => $this->getCacheHitRate('route'),
                'distance_hit_rate' => $this->getCacheHitRate('distance'),
            ],
            'tracking' => [
                'courier_updates' => $this->getCounter('tracking_courier_total'),
                'doctor_updates' => $this->getCounter('tracking_doctor_total'),
                'patient_updates' => $this->getCounter('tracking_patient_total'),
            ],
            'circuit_breaker' => [
                'yandex_opens' => $this->getCounter('circuit_breaker_yandex_open'),
                'osm_opens' => $this->getCounter('circuit_breaker_osm_open'),
            ],
        ];
    }

    private function formatCounter(string $name, float $value, string $help): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} counter\n{$name} {$value}";
    }

    private function formatGauge(string $name, float $value, string $help): string
    {
        return "# HELP {$name} {$help}\n# TYPE {$name} gauge\n{$name} {$value}";
    }

    private function getCounter(string $suffix): float
    {
        return (float) $this->redis->connection()->get(self::METRICS_PREFIX . $suffix) ?: 0;
    }

    private function getSuccessRate(string $type): float
    {
        $total = $this->getCounter("{$type}_total");
        $success = $this->getCounter("{$type}_success");

        return $total > 0 ? ($success / $total) : 0;
    }

    private function getAverageLatency(string $type): float
    {
        $total = $this->getCounter("{$type}_latency_total");
        $count = $this->getCounter("{$type}_latency_count");

        return $count > 0 ? ($total / $count) : 0;
    }

    private function getAverageDistance(): float
    {
        $total = $this->getCounter('route_distance_km_total');
        $count = $this->getCounter('route_total');

        return $count > 0 ? ($total / $count / 1000) : 0;
    }

    private function getCacheHitRate(string $type): float
    {
        $total = $this->getCounter("cache_{$type}_total");
        $hits = $this->getCounter("cache_{$type}_hit");

        return $total > 0 ? ($hits / $total) : 0;
    }

    public function resetMetrics(): void
    {
        $pattern = self::METRICS_PREFIX . '*';
        $keys = $this->redis->connection()->keys($pattern);

        if (!empty($keys)) {
            $this->redis->connection()->del($keys);
        }

        $this->logger->channel('geo')->info('Geo telemetry metrics reset');
    }
}
