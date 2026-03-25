<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Str;

final class TimeSeriesHeatmapService
{
    private ClickHouseService $clickHouseService;
    private string $correlationId;

    public function __construct(ClickHouseService $clickHouseService)
    {
        $this->clickHouseService = $clickHouseService;
        $this->correlationId = Str::uuid()->toString();
    }

    /**
     * Get time-series geo heatmap data
     *
     * @param int $tenantId
     * @param string $vertical
     * @param string $fromDate (YYYY-MM-DD)
     * @param string $toDate (YYYY-MM-DD)
     * @param string $aggregation (hourly|daily|weekly)
     * @param string $metric (event_count|unique_users|unique_sessions)
     * @return array
     */
    public function getGeoTimeSeries(
        int $tenantId,
        string $vertical,
        string $fromDate,
        string $toDate,
        string $aggregation = 'daily',
        string $metric = 'event_count'
    ): array {
        $cacheKey = "heatmap:geo:timeseries:{$tenantId}:{$vertical}:{$fromDate}:{$toDate}:{$aggregation}:{$metric}";

        // Try cache first
        if ($cached = $this->cache->get($cacheKey)) {
            $this->log->channel('analytics')->info('[TimeSeriesHeatmap] Cache hit', [
                'cache_key' => $cacheKey,
                'correlation_id' => $this->correlationId,
            ]);

            return $cached;
        }

        try {
            $data = match ($aggregation) {
                'hourly' => $this->clickHouseService->queryGeoHourly(
                    $tenantId,
                    $vertical,
                    $fromDate,
                    $toDate,
                    $metric
                ),
                'weekly' => $this->clickHouseService->queryGeoWeekly(
                    $tenantId,
                    $vertical,
                    $fromDate,
                    $toDate
                ),
                default => $this->clickHouseService->queryGeoDaily(
                    $tenantId,
                    $vertical,
                    $fromDate,
                    $toDate,
                    $metric
                ),
            };

            $result = $this->formatTimeSeriesResponse(
                data: $data,
                heatmapType: 'geo',
                aggregation: $aggregation,
                metric: $metric,
                tenantId: $tenantId
            );

            // Cache based on aggregation type
            $ttl = match ($aggregation) {
                'hourly' => 5 * 60, // 5 minutes (data changes frequently)
                'weekly' => 24 * 60 * 60, // 24 hours (stable)
                default => 60 * 60, // 1 hour
            };

            $this->cache->put($cacheKey, $result, $ttl);

            $this->log->channel('audit')->info('[TimeSeriesHeatmap] Geo heatmap generated', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'aggregation' => $aggregation,
                'record_count' => count($data),
                'ttl_seconds' => $ttl,
                'correlation_id' => $this->correlationId,
            ]);

            return $result;
        } catch (Exception $e) {
            $this->log->channel('error')->error('[TimeSeriesHeatmap] Geo heatmap generation failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get time-series click heatmap data
     */
    public function getClickTimeSeries(
        int $tenantId,
        string $vertical,
        string $pageUrl,
        string $fromDate,
        string $toDate,
        string $aggregation = 'daily'
    ): array {
        $cacheKey = "heatmap:click:timeseries:{$tenantId}:{$vertical}:" . md5($pageUrl) . ":{$fromDate}:{$toDate}:{$aggregation}";

        // Try cache first
        if ($cached = $this->cache->get($cacheKey)) {
            $this->log->channel('analytics')->info('[TimeSeriesHeatmap] Cache hit (click)', [
                'cache_key' => substr($cacheKey, 0, 50) . '...',
                'correlation_id' => $this->correlationId,
            ]);

            return $cached;
        }

        try {
            $data = match ($aggregation) {
                'hourly' => $this->clickHouseService->queryClickHourly(
                    $tenantId,
                    $vertical,
                    $pageUrl,
                    $fromDate,
                    $toDate
                ),
                default => $this->clickHouseService->queryClickDaily(
                    $tenantId,
                    $vertical,
                    $pageUrl,
                    $fromDate,
                    $toDate
                ),
            };

            $result = $this->formatTimeSeriesResponse(
                data: $data,
                heatmapType: 'click',
                aggregation: $aggregation,
                metric: 'click_count',
                tenantId: $tenantId,
                pageUrl: $pageUrl
            );

            // Cache based on aggregation type
            $ttl = match ($aggregation) {
                'hourly' => 5 * 60,
                default => 60 * 60,
            };

            $this->cache->put($cacheKey, $result, $ttl);

            $this->log->channel('audit')->info('[TimeSeriesHeatmap] Click heatmap generated', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'page_url' => substr($pageUrl, 0, 100),
                'aggregation' => $aggregation,
                'record_count' => count($data),
                'correlation_id' => $this->correlationId,
            ]);

            return $result;
        } catch (Exception $e) {
            $this->log->channel('error')->error('[TimeSeriesHeatmap] Click heatmap generation failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'correlation_id' => $this->correlationId,
                'stacktrace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Format raw ClickHouse data into API response
     */
    private function formatTimeSeriesResponse(
        array $data,
        string $heatmapType,
        string $aggregation,
        string $metric,
        int $tenantId,
        ?string $pageUrl = null
    ): array {
        // Calculate totals
        $totalMetric = array_sum(array_map(
            fn($row) => match ($heatmapType) {
                'geo' => (int) $row['event_count'],
                'click' => (int) $row['click_count'],
            },
            $data
        ));

        $uniqueUsers = array_sum(array_map(
            fn($row) => (int) ($row['unique_users'] ?? 0),
            $data
        ));

        return [
            'heatmap_type' => $heatmapType,
            'aggregation' => $aggregation,
            'metric' => $metric,
            'data' => $data,
            'metadata' => [
                'total_metric' => $totalMetric,
                'total_unique_users' => $uniqueUsers,
                'period_type' => $aggregation,
                'record_count' => count($data),
                'generated_at' => now()->toIso8601String(),
                'correlation_id' => $this->correlationId,
            ],
        ];
    }

    /**
     * Invalidate heatmap cache for tenant
     */
    public function invalidateCache(int $tenantId, string $vertical = '*'): void
    {
        $pattern = "heatmap:*:{$tenantId}:{$vertical}*";
        $this->cache->tags(['heatmap', "tenant:{$tenantId}"])->flush();

        $this->log->channel('audit')->info('[TimeSeriesHeatmap] Cache invalidated', [
            'tenant_id' => $tenantId,
            'vertical' => $vertical,
            'correlation_id' => $this->correlationId,
        ]);
    }

    public function setCorrelationId(string $correlationId): self
    {
        $this->correlationId = $correlationId;
        $this->clickHouseService->setCorrelationId($correlationId);

        return $this;
    }
}
