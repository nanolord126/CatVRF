<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Domains\Analytics\Services\TimeSeriesHeatmapService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Str;

final class TimeSeriesHeatmapController extends Controller
{
    private TimeSeriesHeatmapService $timeSeriesService;

    public function __construct(TimeSeriesHeatmapService $timeSeriesService)
    {
        $this->timeSeriesService = $timeSeriesService;
    }

    /**
     * GET /api/analytics/heatmaps/timeseries/geo
     */
    public function geoTimeSeries(Request $request): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            $this->timeSeriesService->setCorrelationId($correlationId);

            // Validate input
            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'from_date' => 'required|date_format:Y-m-d',
                'to_date' => 'required|date_format:Y-m-d|after:from_date',
                'aggregation' => 'sometimes|in:hourly,daily,weekly',
                'metric' => 'sometimes|in:event_count,unique_users,unique_sessions',
            ]);

            $tenantId = auth()->id() ?? filament()->getTenant()->id;
            $vertical = $validated['vertical'];
            $fromDate = $validated['from_date'];
            $toDate = $validated['to_date'];
            $aggregation = $validated['aggregation'] ?? 'daily';
            $metric = $validated['metric'] ?? 'event_count';

            // Rate limiting
            $cacheKey = "ratelimit:timeseries:{$tenantId}:{$vertical}";
            if (cache()->has($cacheKey) && cache()->get($cacheKey) > 100) {
                $this->log->channel('fraud_alert')->warning('[TimeSeriesHeatmap] Rate limit exceeded', [
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'error' => 'Too many requests',
                    'correlation_id' => $correlationId,
                ], 429);
            }

            cache()->increment($cacheKey, 1, 60);

            // Get data from service
            $data = $this->timeSeriesService->getGeoTimeSeries(
                tenantId: $tenantId,
                vertical: $vertical,
                fromDate: $fromDate,
                toDate: $toDate,
                aggregation: $aggregation,
                metric: $metric
            );

            $this->log->channel('audit')->info('[TimeSeriesHeatmap] Geo heatmap requested', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'aggregation' => $aggregation,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'data' => $data,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->log->channel('error')->warning('[TimeSeriesHeatmap] Validation failed', [
                'errors' => $e->errors(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'correlation_id' => $correlationId ?? 'unknown',
            ], 422);
        } catch (\Exception $e) {
            $this->log->channel('error')->error('[TimeSeriesHeatmap] Geo heatmap request failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
                'stacktrace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId ?? 'unknown',
            ], 500);
        }
    }

    /**
     * GET /api/analytics/heatmaps/timeseries/click
     */
    public function clickTimeSeries(Request $request): JsonResponse
    {
        try {
            $correlationId = $request->header('X-Correlation-ID', Str::uuid()->toString());
            $this->timeSeriesService->setCorrelationId($correlationId);

            // Validate input
            $validated = $request->validate([
                'vertical' => 'required|string|max:50',
                'page_url' => 'required|url|max:500',
                'from_date' => 'required|date_format:Y-m-d',
                'to_date' => 'required|date_format:Y-m-d|after:from_date',
                'aggregation' => 'sometimes|in:hourly,daily',
            ]);

            $tenantId = auth()->id() ?? filament()->getTenant()->id;
            $vertical = $validated['vertical'];
            $pageUrl = $validated['page_url'];
            $fromDate = $validated['from_date'];
            $toDate = $validated['to_date'];
            $aggregation = $validated['aggregation'] ?? 'daily';

            // Rate limiting
            $cacheKey = "ratelimit:timeseries:click:{$tenantId}";
            if (cache()->has($cacheKey) && cache()->get($cacheKey) > 100) {
                $this->log->channel('fraud_alert')->warning('[TimeSeriesHeatmap] Rate limit exceeded (click)', [
                    'tenant_id' => $tenantId,
                    'correlation_id' => $correlationId,
                ]);

                return response()->json([
                    'error' => 'Too many requests',
                    'correlation_id' => $correlationId,
                ], 429);
            }

            cache()->increment($cacheKey, 1, 60);

            // Get data from service
            $data = $this->timeSeriesService->getClickTimeSeries(
                tenantId: $tenantId,
                vertical: $vertical,
                pageUrl: $pageUrl,
                fromDate: $fromDate,
                toDate: $toDate,
                aggregation: $aggregation
            );

            $this->log->channel('audit')->info('[TimeSeriesHeatmap] Click heatmap requested', [
                'tenant_id' => $tenantId,
                'vertical' => $vertical,
                'page_url' => substr($pageUrl, 0, 100),
                'aggregation' => $aggregation,
                'correlation_id' => $correlationId,
            ]);

            return response()->json([
                'data' => $data,
                'correlation_id' => $correlationId,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->log->channel('error')->warning('[TimeSeriesHeatmap] Validation failed (click)', [
                'errors' => $e->errors(),
                'correlation_id' => $correlationId ?? 'unknown',
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors(),
                'correlation_id' => $correlationId ?? 'unknown',
            ], 422);
        } catch (\Exception $e) {
            $this->log->channel('error')->error('[TimeSeriesHeatmap] Click heatmap request failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId ?? 'unknown',
                'stacktrace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'correlation_id' => $correlationId ?? 'unknown',
            ], 500);
        }
    }
}
