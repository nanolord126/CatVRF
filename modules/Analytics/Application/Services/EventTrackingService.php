<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Services;

use App\Traits\WithAuditLogging;
use App\Traits\WithOpenTelemetryTracing;
use App\Traits\WithPrometheusMetrics;
use App\Services\AuditService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\DatabaseManager;

/**
 * Event Tracking Service
 *
 * Handles real-time event tracking and metric increment operations.
 * Follows Single Responsibility Principle - only handles event ingestion.
 */
final readonly class EventTrackingService
{
    use WithAuditLogging;
    use WithOpenTelemetryTracing;
    use WithPrometheusMetrics;

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly CacheManager $cache,
        private readonly AuditService $auditService,
    ) {}

    /**
     * Track an analytics event.
     * 
     * Events are stored in analytics_events table and processed asynchronously.
     * Use this for real-time event tracking (page views, clicks, etc.).
     */
    public function trackEvent(
        string $eventType,
        int $tenantId,
        ?int $userId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        array $metadata = [],
        ?float $monetaryValue = null,
        array $dimensions = [],
    ): void {
        $this->traceWithAttributes(
            name: 'analytics.track_event',
            attributes: [
                'analytics.event_type' => $eventType,
                'analytics.tenant_id' => $tenantId,
                'analytics.user_id' => $userId,
                'analytics.entity_type' => $entityType,
                'analytics.entity_id' => $entityId,
            ],
            callback: function () use (
                $eventType,
                $tenantId,
                $userId,
                $entityType,
                $entityId,
                $metadata,
                $monetaryValue,
                $dimensions
            ) {
                // Record metrics
                $this->incrementCounter(
                    'events_tracked_total',
                    ['event_type' => $eventType, 'tenant_id' => $tenantId]
                );

                // Fraud check before processing
                // TODO: Integrate with FraudDetectionService when available

                $this->db->table('analytics_events')->insert([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'event_type' => $eventType,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'metadata' => json_encode($metadata),
                    'category' => $dimensions['category'] ?? null,
                    'seller_id' => $dimensions['seller_id'] ?? null,
                    'product_id' => $dimensions['product_id'] ?? null,
                    'device' => $dimensions['device'] ?? null,
                    'os' => $dimensions['os'] ?? null,
                    'browser' => $dimensions['browser'] ?? null,
                    'traffic_source' => $dimensions['traffic_source'] ?? null,
                    'utm_medium' => $dimensions['utm_medium'] ?? null,
                    'utm_source' => $dimensions['utm_source'] ?? null,
                    'utm_campaign' => $dimensions['utm_campaign'] ?? null,
                    'country' => $dimensions['country'] ?? null,
                    'region' => $dimensions['region'] ?? null,
                    'city' => $dimensions['city'] ?? null,
                    'monetary_value' => $monetaryValue,
                    'occurred_at' => CarbonImmutable::now(),
                    'processed_at' => null,
                    'created_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ]);

                // Log audit
                $this->logAction(
                    action: 'event_tracked',
                    entityType: 'AnalyticsEvent',
                    entityId: null,
                    context: [
                        'event_type' => $eventType,
                        'tenant_id' => $tenantId,
                        'user_id' => $userId,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ],
                    userId: $userId,
                    tenantId: $tenantId
                );

                // Invalidate cache for affected metrics
                $this->invalidateMetricsCache($tenantId);
            }
        );
    }

    /**
     * Increment a metric counter.
     * 
     * Use this for simple counter increments (order count, revenue, etc.).
     * Updates are batched and persisted via aggregation jobs.
     */
    public function increment(
        string $metricType,
        int $tenantId,
        float|int $value = 1,
        ?CarbonImmutable $date = null,
        ?int $sellerId = null,
        ?int $productId = null,
        ?int $userId = null,
    ): void {
        $this->traceWithAttributes(
            name: 'analytics.increment_metric',
            attributes: [
                'analytics.metric_type' => $metricType,
                'analytics.tenant_id' => $tenantId,
                'analytics.value' => $value,
            ],
            callback: function () use (
                $metricType,
                $tenantId,
                $value,
                $date,
                $sellerId,
                $productId,
                $userId
            ) {
                // Record metrics
                $this->incrementCounter(
                    'metrics_incremented_total',
                    ['metric_type' => $metricType, 'tenant_id' => $tenantId],
                    $value
                );

                // Fraud check for metric increments
                // TODO: Integrate with FraudDetectionService when available

                $date = $date ?? CarbonImmutable::now();
                $cacheKey = $this->getMetricCacheKey($metricType, $tenantId, $date, $sellerId, $productId, $userId);

                // Use Redis counter for real-time increments
                $this->cache->increment($cacheKey, $value);

                // Mark for aggregation
                $this->cache->put("analytics:pending_aggregation:{$tenantId}:{$date->toDateString()}", true, 3600);
            }
        );
    }

    /**
     * Get cache key for metric.
     */
    private function getMetricCacheKey(
        string $metricType,
        int $tenantId,
        CarbonImmutable $date,
        ?int $sellerId = null,
        ?int $productId = null,
        ?int $userId = null,
    ): string {
        $key = "analytics:metric:{$metricType}:{$tenantId}:{$date->toDateString()}";
        
        if ($sellerId !== null) {
            $key .= ":seller:{$sellerId}";
        }
        if ($productId !== null) {
            $key .= ":product:{$productId}";
        }
        if ($userId !== null) {
            $key .= ":user:{$userId}";
        }

        return $key;
    }

    /**
     * Invalidate metrics cache for a tenant.
     */
    private function invalidateMetricsCache(int $tenantId): void
    {
        $this->cache->tags(["analytics:{$tenantId}"])->flush();
    }
}
