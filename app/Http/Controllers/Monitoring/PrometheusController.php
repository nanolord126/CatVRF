<?php

declare(strict_types=1);

namespace App\Http\Controllers\Monitoring;

use App\Domains\Shared\Monitoring\Services\QueueMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Prometheus\RenderTextFormat;
use Prometheus\CollectorRegistry;

/**
 * Prometheus Controller
 * 
 * Exposes Prometheus metrics endpoint for monitoring and alerting.
 * Integrates with QueueMetricsService to collect real-time queue statistics.
 * 
 * Endpoints:
 * - GET /metrics - Prometheus metrics in text format
 * - GET /monitoring/health - Health check endpoint
 * - GET /monitoring/metrics/json - Metrics in JSON format
 * - GET /monitoring/alerts - Current queue alerts
 * 
 * @version 2026.1
 */
final class PrometheusController
{
    public function __construct(
        private QueueMetricsService $queueMetricsService,
        private CollectorRegistry $registry
    ) {
    }

    /**
     * Serve Prometheus metrics in text format.
     * This is the standard endpoint scraped by Prometheus.
     */
    public function metrics(Request $request): Response
    {
        try {
            // Register and update metrics before serving
            $this->queueMetricsService->registerMetrics();
            $this->queueMetricsService->updateMetrics();

            // Render metrics in Prometheus text format
            $renderer = new RenderTextFormat();
            $metricFamilies = $this->registry->getMetricFamilySamples();

            $output = $renderer->render($metricFamilies);

            Log::debug('Prometheus metrics served', [
                'metric_families' => count($metricFamilies),
                'output_size' => strlen($output),
            ]);

            return response($output, 200, [
                'Content-Type' => RenderTextFormat::MIME_TYPE,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to serve Prometheus metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Metrics collection failed', 500, [
                'Content-Type' => 'text/plain',
            ]);
        }
    }

    /**
     * Health check endpoint for monitoring systems.
     */
    public function health(Request $request): Response
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ];

        // Check database connection
        try {
            \DB::connection()->getPdo();
            $health['database'] = 'connected';
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['database'] = 'disconnected';
            Log::warning('Database health check failed', ['error' => $e->getMessage()]);
        }

        // Check Redis connection
        try {
            \Cache::store('redis')->get('health_check_key', 'ok');
            $health['cache'] = 'connected';
        } catch (\Exception $e) {
            $health['status'] = 'degraded';
            $health['cache'] = 'disconnected';
            Log::warning('Redis health check failed', ['error' => $e->getMessage()]);
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Serve metrics in JSON format for debugging and API consumers.
     */
    public function metricsJson(Request $request): Response
    {
        try {
            $metrics = $this->queueMetricsService->updateMetrics();
            $horizonStats = $this->queueMetricsService->getHorizonStats();
            $alerts = $this->queueMetricsService->getAlerts();

            $response = [
                'timestamp' => now()->toIso8601String(),
                'metrics' => $metrics,
                'horizon' => $horizonStats,
                'alerts' => $alerts,
                'health_status' => empty($alerts) ? 'healthy' : 'degraded',
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Failed to serve JSON metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Metrics collection failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current queue alerts.
     */
    public function alerts(Request $request): Response
    {
        try {
            $alerts = $this->queueMetricsService->getAlerts();

            return response()->json([
                'timestamp' => now()->toIso8601String(),
                'alerts' => $alerts,
                'total' => count($alerts),
                'critical_count' => count(array_filter($alerts, fn($a) => $a['severity'] === 'critical')),
                'warning_count' => count(array_filter($alerts, fn($a) => $a['severity'] === 'warning')),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get alerts', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve alerts',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Horizon statistics.
     */
    public function horizonStats(Request $request): Response
    {
        try {
            $stats = $this->queueMetricsService->getHorizonStats();

            return response()->json([
                'timestamp' => now()->toIso8601String(),
                'horizon' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get Horizon stats', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve Horizon statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export metrics in specific format.
     */
    public function export(Request $request, string $format = 'json'): Response
    {
        try {
            $export = $this->queueMetricsService->exportMetrics($format);

            $contentType = match ($format) {
                'json' => 'application/json',
                'prometheus' => 'text/plain',
                default => 'application/json',
            };

            return response($export, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => "attachment; filename=metrics_{$format}_" . now()->format('Y-m-d_H-i-s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to export metrics', [
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
