<?php declare(strict_types=1);

namespace App\Services\Cache;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use Spatie\Prometheus\Facades\Prometheus;
use Illuminate\Support\Str;

/**
 * Cache Metrics Service
 *
 * Production 2026 CANON - Cache Metrics for Prometheus
 *
 * Exports cache-related metrics to Prometheus for monitoring and observability.
 * All metrics follow Prometheus best practices with low-cardinality labels.
 *
 * Metrics exported:
 * - cache_hits_total (Counter) - Total cache hits
 * - cache_misses_total (Counter) - Total cache misses
 * - cache_write_latency_seconds (Histogram) - Cache write latency
 * - cache_read_latency_seconds (Histogram) - Cache read latency
 * - cache_invalidation_total (Counter) - Total cache invalidations
 * - cache_memory_usage_bytes (Gauge) - Cache memory usage per vertical
 * - cache_lock_timeout_total (Counter) - Cache lock timeout count
 * - cache_error_total (Counter) - Cache operation errors
 *
 * @author CatVRF Team
 * @version 2026.04.18
 */
final readonly class CacheMetricsService
{
    private const NAMESPACE = 'catvrf';

    public function __construct(
        private readonly LogManager $logger,
    ) {}

    /**
     * Record cache hit (Counter)
     */
    public function recordCacheHit(array $tags, string $key): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_cache_hits_total')
            ->help('Total cache hits')
            ->label('vertical', $this->sanitizeLabel($this->extractVerticalFromTags($tags)))
            ->label('tags', $this->sanitizeLabel(implode(',', $tags)))
            ->inc();

        $this->logger->debug('Cache hit recorded', [
            'key' => $key,
            'tags' => $tags,
        ]);
    }

    /**
     * Record cache miss (Counter)
     */
    public function recordCacheMiss(array $tags, string $key, int $ttl): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_cache_misses_total')
            ->help('Total cache misses')
            ->label('vertical', $this->sanitizeLabel($this->extractVerticalFromTags($tags)))
            ->label('tags', $this->sanitizeLabel(implode(',', $tags)))
            ->label('ttl', (string) $ttl)
            ->inc();

        $this->logger->debug('Cache miss recorded', [
            'key' => $key,
            'tags' => $tags,
            'ttl' => $ttl,
        ]);
    }

    /**
     * Record cache write latency (Histogram)
     */
    public function recordCacheWriteLatency(float $latency, array $tags, string $key): void
    {
        Prometheus::addHistogram()
            ->name(self::NAMESPACE . '_cache_write_latency_seconds')
            ->help('Cache write latency in seconds')
            ->label('vertical', $this->sanitizeLabel($this->extractVerticalFromTags($tags)))
            ->label('tags', $this->sanitizeLabel(implode(',', $tags)))
            ->observe($latency);
    }

    /**
     * Record cache read latency (Histogram)
     */
    public function recordCacheReadLatency(float $latency, array $tags, string $key): void
    {
        Prometheus::addHistogram()
            ->name(self::NAMESPACE . '_cache_read_latency_seconds')
            ->help('Cache read latency in seconds')
            ->label('vertical', $this->sanitizeLabel($this->extractVerticalFromTags($tags)))
            ->label('tags', $this->sanitizeLabel(implode(',', $tags)))
            ->observe($latency);
    }

    /**
     * Record cache invalidation (Counter)
     */
    public function recordCacheInvalidation(array $tags, string $reason): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_cache_invalidation_total')
            ->help('Total cache invalidations')
            ->label('vertical', $this->sanitizeLabel($this->extractVerticalFromTags($tags)))
            ->label('tags', $this->sanitizeLabel(implode(',', $tags)))
            ->label('reason', $this->sanitizeLabel($reason))
            ->inc();

        $this->logger->info('Cache invalidation recorded', [
            'tags' => $tags,
            'reason' => $reason,
        ]);
    }

    /**
     * Record cache memory usage (Gauge)
     */
    public function recordCacheMemoryUsage(int $bytes, string $vertical, string $pattern): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_cache_memory_usage_bytes')
            ->help('Cache memory usage in bytes')
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->label('pattern', $this->sanitizeLabel($pattern))
            ->set($bytes);

        $this->logger->debug('Cache memory usage recorded', [
            'vertical' => $vertical,
            'bytes' => $bytes,
            'mb' => round($bytes / 1024 / 1024, 2),
        ]);
    }

    /**
     * Record cache lock timeout (Counter)
     */
    public function recordCacheLockTimeout(string $key, int $timeout): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_cache_lock_timeout_total')
            ->help('Cache lock timeout count')
            ->label('timeout', (string) $timeout)
            ->inc();

        $this->logger->warning('Cache lock timeout recorded', [
            'key' => $key,
            'timeout' => $timeout,
        ]);
    }

    /**
     * Record cache error (Counter)
     */
    public function recordCacheError(string $operation, string $key, string $error): void
    {
        Prometheus::addCounter()
            ->name(self::NAMESPACE . '_cache_error_total')
            ->help('Cache operation errors')
            ->label('operation', $this->sanitizeLabel($operation))
            ->label('error_type', $this->sanitizeLabel($error))
            ->inc();

        $this->logger->error('Cache error recorded', [
            'operation' => $operation,
            'key' => $key,
            'error' => $error,
        ]);
    }

    /**
     * Record cache key count (Gauge)
     */
    public function recordCacheKeyCount(int $count, string $vertical, string $pattern): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_cache_key_count')
            ->help('Cache key count')
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->label('pattern', $this->sanitizeLabel($pattern))
            ->set($count);

        $this->logger->debug('Cache key count recorded', [
            'vertical' => $vertical,
            'count' => $count,
            'pattern' => $pattern,
        ]);
    }

    /**
     * Calculate and record cache hit rate (Gauge)
     */
    public function recordCacheHitRate(float $hitRate, string $vertical): void
    {
        Prometheus::addGauge()
            ->name(self::NAMESPACE . '_cache_hit_rate')
            ->help('Cache hit rate (0-1)')
            ->label('vertical', $this->sanitizeLabel($vertical))
            ->set($hitRate);

        $this->logger->debug('Cache hit rate recorded', [
            'vertical' => $vertical,
            'hit_rate' => $hitRate,
            'percentage' => round($hitRate * 100, 2) . '%',
        ]);
    }

    /**
     * Extract vertical from tags array
     */
    private function extractVerticalFromTags(array $tags): string
    {
        // Try to find vertical in tags (e.g., 'medical', 'beauty', etc.)
        foreach ($tags as $tag) {
            if (in_array($tag, ['medical', 'beauty', 'food', 'fashion', 'travel', 'auto', 'hotels', 'fitness', 'sports', 'luxury', 'insurance', 'legal', 'logistics', 'education', 'delivery', 'payment', 'analytics', 'consulting', 'content', 'freelance', 'event_planning', 'staff', 'inventory', 'taxi', 'tickets', 'wallet', 'pet', 'wedding_planning', 'veterinary', 'toys_and_games', 'advertising', 'car_rental', 'finances', 'flowers', 'furniture', 'pharmacy', 'photography', 'short_term_rentals', 'sports_nutrition', 'personal_development', 'home_services', 'gardening', 'geo', 'geo_logistics', 'grocery_and_delivery', 'farm_direct', 'meat_shops', 'office_catering', 'party_supplies', 'confectionery', 'construction_and_repair', 'cleaning_services', 'communication', 'books_and_literature', 'collectibles', 'hobby_and_craft', 'household_goods', 'marketplace', 'music_and_instruments', 'vegan_products', 'art'])) {
                return $tag;
            }
        }

        return 'unknown';
    }

    /**
     * Sanitize label value for Prometheus
     * Prevents high cardinality and invalid characters
     */
    private function sanitizeLabel(string $value): string
    {
        // Remove or replace invalid characters
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $value);
        
        // Limit length to prevent cardinality issues
        return substr($sanitized, 0, 50);
    }
}
