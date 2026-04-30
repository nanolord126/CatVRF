<?php

declare(strict_types=1);

namespace Modules\BigData\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BigData\Application\Services\BigDataMonitoringFacade;
use Modules\BigData\Domain\Interfaces\MetricsExporterInterface;

/**
 * Metrics Controller
 *
 * Exposes Prometheus-format metrics for scraping by Prometheus server.
 * Route: GET /metrics/bigdata
 *
 * This endpoint is unauthenticated — secured at network level
 * (only accessible from Prometheus scrape network).
 *
 * Returns metrics in Prometheus text exposition format.
 * Also supports JSON format via ?format=json query parameter.
 */
final class MetricsController extends Controller
{
    public function __construct(
        private readonly MetricsExporterInterface $exporter,
    ) {}

    /**
     * Render Prometheus metrics
     *
     * Supports two formats:
     * - Default: Prometheus text exposition format (text/plain)
     * - ?format=json: JSON wrapper with metadata
     */
    public function __invoke(Request $request): JsonResponse
    {
        $format = $request->query('format', 'prometheus');

        try {
            $this->exporter->refresh();
            $output = $this->exporter->render();

            if ($format === 'json') {
                return response()->json([
                    'status' => 'ok',
                    'format' => 'prometheus_text',
                    'metrics' => $output,
                    'content_type' => $this->exporter->getContentType(),
                    'scraped_at' => now()->toIso8601String(),
                ]);
            }

            // Prometheus expects text/plain, not JSON
            return response()->json([
                'status' => 'ok',
                'metrics' => $output,
            ], 200, [
                'Content-Type' => $this->exporter->getContentType(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to render metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
