<?php declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class TimeSeriesHeatmapController extends Controller
{

    private TimeSeriesHeatmapService $timeSeriesService;
        public function __construct(TimeSeriesHeatmapService $timeSeriesService,
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    )
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
                $tenantId = $this->guard->id() ?? filament()->getTenant()->id;
                $vertical = $validated['vertical'];
                $fromDate = $validated['from_date'];
                $toDate = $validated['to_date'];
                $aggregation = $validated['aggregation'] ?? 'daily';
                $metric = $validated['metric'] ?? 'event_count';
                // Rate limiting
                $cacheKey = "ratelimit:timeseries:{$tenantId}:{$vertical}";
                if (cache()->has($cacheKey) && cache()->get($cacheKey) > 100) {
                    $this->logger->channel('fraud_alert')->warning('[TimeSeriesHeatmap] Rate limit exceeded', [
                        'tenant_id' => $tenantId,
                        'correlation_id' => $correlationId,
                    ]);
                    return $this->response->json([
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
                $this->logger->channel('audit')->info('[TimeSeriesHeatmap] Geo heatmap requested', [
                    'tenant_id' => $tenantId,
                    'vertical' => $vertical,
                    'aggregation' => $aggregation,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'data' => $data,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->logger->channel('error')->warning('[TimeSeriesHeatmap] Validation failed', [
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);
                return $this->response->json([
                    'error' => 'Validation failed',
                    'messages' => $e->errors(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 422);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('[TimeSeriesHeatmap] Geo heatmap request failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                    'stacktrace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
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
                $tenantId = $this->guard->id() ?? filament()->getTenant()->id;
                $vertical = $validated['vertical'];
                $pageUrl = $validated['page_url'];
                $fromDate = $validated['from_date'];
                $toDate = $validated['to_date'];
                $aggregation = $validated['aggregation'] ?? 'daily';
                // Rate limiting
                $cacheKey = "ratelimit:timeseries:click:{$tenantId}";
                if (cache()->has($cacheKey) && cache()->get($cacheKey) > 100) {
                    $this->logger->channel('fraud_alert')->warning('[TimeSeriesHeatmap] Rate limit exceeded (click)', [
                        'tenant_id' => $tenantId,
                        'correlation_id' => $correlationId,
                    ]);
                    return $this->response->json([
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
                $this->logger->channel('audit')->info('[TimeSeriesHeatmap] Click heatmap requested', [
                    'tenant_id' => $tenantId,
                    'vertical' => $vertical,
                    'page_url' => substr($pageUrl, 0, 100),
                    'aggregation' => $aggregation,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'data' => $data,
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->logger->channel('error')->warning('[TimeSeriesHeatmap] Validation failed (click)', [
                    'errors' => $e->errors(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ]);
                return $this->response->json([
                    'error' => 'Validation failed',
                    'messages' => $e->errors(),
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 422);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('[TimeSeriesHeatmap] Click heatmap request failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId ?? 'unknown',
                    'stacktrace' => $e->getTraceAsString(),
                ]);
                return $this->response->json([
                    'error' => 'Internal server error',
                    'correlation_id' => $correlationId ?? 'unknown',
                ], 500);
            }
        }
}
