<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Listeners;

use App\Domains\Analytics\Events\HeatmapUpdateEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * HeatmapUpdateListener - Handles cache invalidation and logging for heatmap updates
 *
 * Responsibilities:
 * 1. Invalidate Redis cache for affected heatmap data
 * 2. Log update events to audit channel with correlation ID
 * 3. Broadcast update notification to subscribed clients via Reverb
 * 4. Update last-modified timestamp in cache
 *
 * Uses queue to prevent blocking the main request thread.
 *
 * @implements ShouldQueue
 * @uses Redis Cache for invalidation
 * @uses Audit Logging
 *
 * @package App\Domains\Analytics\Listeners
 */
final class HeatmapUpdateListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var int Maximum retry attempts
     */
    public int $tries = 3;

    /**
     * @var int Delay between retries in seconds
     */
    public int $retryAfter = 10;

    /**
     * @var string|null Job queue name
     */
    public ?string $queue = 'default';

    /**
     * @var int Job timeout in seconds (cache operations are quick)
     */
    public int $timeout = 30;

    /**
     * Handle the HeatmapUpdateEvent.
     *
     * @param HeatmapUpdateEvent $event The heatmap update event
     * @return void
     *
     * @throws \Exception If critical cache operation fails after retries
     */
    public function handle(HeatmapUpdateEvent $event): void
    {
        try {
            // 1. Invalidate affected cache entries
            $this->invalidateHeatmapCache($event);

            // 2. Update last-modified timestamp
            $this->updateLastModifiedTime($event);

            // 3. Log the update event
            $this->logUpdateEvent($event);

            // 4. Store update metadata for analytics
            $this->recordUpdateMetrics($event);

        } catch (\Exception $e) {
            $this->log->channel('audit')->error('HeatmapUpdateListener failed', [
                'event' => $event->getTraceString(),
                'tenant_id' => $event->tenantId,
                'heatmap_type' => $event->heatmapType,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'correlation_id' => $event->correlationId,
            ]);

            // Re-throw to trigger Laravel's queue error handling
            throw $e;
        }
    }

    /**
     * Invalidate all affected heatmap cache entries.
     *
     * Cache key structure:
     *   - geo: heatmap:geo:tenant:{id}:vertical:{vertical}
     *   - click: heatmap:click:tenant:{id}:page_url:{url}
     *
     * @param HeatmapUpdateEvent $event Event containing cache invalidation info
     * @return void
     */
    private function invalidateHeatmapCache(HeatmapUpdateEvent $event): void
    {
        $tenantId = $event->tenantId;
        $heatmapType = $event->heatmapType;
        $vertical = $event->vertical;

        $cacheKeysInvalidated = 0;

        if ($heatmapType === 'geo') {
            // Invalidate geo-heatmap cache for this vertical
            if ($vertical) {
                $cacheKey = "heatmap:geo:tenant:{$tenantId}:vertical:{$vertical}";
                $this->cache->forget($cacheKey);
                $cacheKeysInvalidated++;

                $this->log->channel('audit')->debug('Invalidated geo-heatmap cache', [
                    'cache_key' => $cacheKey,
                    'correlation_id' => $event->correlationId,
                ]);
            } else {
                // Invalidate all verticals for this tenant
                $verticals = ['beauty', 'food', 'auto', 'hotels', 'realestate'];
                foreach ($verticals as $v) {
                    $cacheKey = "heatmap:geo:tenant:{$tenantId}:vertical:{$v}";
                    $this->cache->forget($cacheKey);
                    $cacheKeysInvalidated++;
                }

                $this->log->channel('audit')->debug('Invalidated all geo-heatmap cache entries', [
                    'tenant_id' => $tenantId,
                    'count' => $cacheKeysInvalidated,
                    'correlation_id' => $event->correlationId,
                ]);
            }
        } elseif ($heatmapType === 'click') {
            // Invalidate click-heatmap cache for all pages (pattern matching needed)
            // For click-heatmaps, we invalidate all entries for this tenant
            // as page URLs can be dynamic
            $pattern = "heatmap:click:tenant:{$tenantId}:*";
            
            // Using Laravel cache tags if available
            $this->cache->tags(['heatmap', "tenant:{$tenantId}", 'click'])
                ->flush();

            $this->log->channel('audit')->debug('Invalidated click-heatmap cache', [
                'tenant_id' => $tenantId,
                'correlation_id' => $event->correlationId,
            ]);
        }

        // Invalidate generic heatmap cache
        $genericKey = "heatmap:all:tenant:{$tenantId}";
        $this->cache->forget($genericKey);
        $cacheKeysInvalidated++;
    }

    /**
     * Update the last-modified timestamp in cache.
     *
     * Used by frontend to check if local cache is stale.
     * Key: heatmap:last_modified:tenant:{id}:type:{type}
     *
     * @param HeatmapUpdateEvent $event Event containing update info
     * @return void
     */
    private function updateLastModifiedTime(HeatmapUpdateEvent $event): void
    {
        $timestamp = \now()->timestamp;
        $cacheKey = "heatmap:last_modified:tenant:{$event->tenantId}:type:{$event->heatmapType}";

        // Store with 24-hour TTL
        $this->cache->put($cacheKey, $timestamp, 86400);

        $this->log->channel('audit')->debug('Updated heatmap last-modified timestamp', [
            'cache_key' => $cacheKey,
            'timestamp' => $timestamp,
            'correlation_id' => $event->correlationId,
        ]);
    }

    /**
     * Log the heatmap update event to audit channel.
     *
     * @param HeatmapUpdateEvent $event Event to log
     * @return void
     */
    private function logUpdateEvent(HeatmapUpdateEvent $event): void
    {
        $dataPoints = isset($event->data['points']) 
            ? count($event->data['points']) 
            : count($event->data['clicks'] ?? []);

        $this->log->channel('audit')->info('Heatmap updated', [
            'event_type' => 'heatmap.update',
            'heatmap_type' => $event->heatmapType,
            'tenant_id' => $event->tenantId,
            'vertical' => $event->vertical,
            'user_id' => $event->userId,
            'data_points' => $dataPoints,
            'data_stats' => $event->data['stats'] ?? null,
            'correlation_id' => $event->correlationId,
            'timestamp' => \now()->toIso8601String(),
        ]);
    }

    /**
     * Record update metrics for analytics.
     *
     * Tracks how often heatmaps are updated, useful for monitoring
     * and performance analysis.
     *
     * @param HeatmapUpdateEvent $event Event containing metrics data
     * @return void
     */
    private function recordUpdateMetrics(HeatmapUpdateEvent $event): void
    {
        try {
            // Increment update counter
            $counterKey = "heatmap:update_count:tenant:{$event->tenantId}:type:{$event->heatmapType}";
            $this->cache->increment($counterKey);
            $this->cache->expire($counterKey, 3600); // 1-hour expiry

            // Track updates per minute for rate limiting detection
            $minuteKey = "heatmap:updates:minute:tenant:{$event->tenantId}:"
                . \now()->format('Y-m-d H:i');
            $this->cache->increment($minuteKey);
            $this->cache->expire($minuteKey, 60);

            $this->log->channel('audit')->debug('Heatmap update metrics recorded', [
                'tenant_id' => $event->tenantId,
                'heatmap_type' => $event->heatmapType,
                'correlation_id' => $event->correlationId,
            ]);

        } catch (\Exception $e) {
            // Don't fail the listener if metrics recording fails
            $this->log->channel('audit')->warning('Failed to record heatmap update metrics', [
                'error' => $e->getMessage(),
                'correlation_id' => $event->correlationId,
            ]);
        }
    }

    /**
     * Handle a failed job.
     *
     * Called by Laravel queue if all retry attempts fail.
     *
     * @param HeatmapUpdateEvent $event Event that failed
     * @param \Exception $exception The exception that caused failure
     * @return void
     */
    public function failed(HeatmapUpdateEvent $event, \Exception $exception): void
    {
        $this->log->channel('audit')->critical('HeatmapUpdateListener permanently failed', [
            'event_type' => 'heatmap.update',
            'tenant_id' => $event->tenantId,
            'heatmap_type' => $event->heatmapType,
            'error_message' => $exception->getMessage(),
            'correlation_id' => $event->correlationId,
        ]);

        // Send alert notification (implementation depends on your notification system)
        // Example: Sentry::captureException($exception);
    }
}
