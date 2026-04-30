<?php

declare(strict_types=1);

namespace Modules\BigData\Presentation\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BigData\Application\Services\BigDataCostFacade;
use Modules\BigData\Infrastructure\Exporters\BigDataCostExporter;

/**
 * Cost Monitoring Controller
 *
 * API endpoints for FinOps / Cost Monitoring.
 */
final class CostController extends Controller
{
    public function __construct(
        private readonly BigDataCostFacade $costFacade,
        private readonly BigDataCostExporter $exporter,
    ) {}

    /**
     * Full cost snapshot
     * GET /api/bigdata/cost/snapshot
     */
    public function snapshot(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getSnapshot(),
        ]);
    }

    /**
     * Daily breakdown
     * GET /api/bigdata/cost/daily?date=2026-04-29
     */
    public function dailyBreakdown(Request $request): JsonResponse
    {
        $date = CarbonImmutable::parse($request->input('date', 'today'));

        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getDailyBreakdown($date)->toArray(),
        ]);
    }

    /**
     * Daily time series
     * GET /api/bigdata/cost/timeseries?start=2026-04-01&end=2026-04-29
     */
    public function timeSeries(Request $request): JsonResponse
    {
        $endDate = CarbonImmutable::parse($request->input('end', 'today'));
        $startDate = CarbonImmutable::parse($request->input('start', $endDate->subDays(30)->toDateString()));

        return response()->json([
            'success' => true,
            'data' => array_map(
                fn($dto) => $dto->toArray(),
                $this->costFacade->getDailyTimeSeries($startDate, $endDate),
            ),
        ]);
    }

    /**
     * Seller attribution
     * GET /api/bigdata/cost/seller/{sellerId}?tenant_id=1&days=30
     */
    public function sellerAttribution(Request $request, int $sellerId): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 1);
        $days = (int) $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getSellerAttribution($tenantId, $sellerId, $days)->toArray(),
        ]);
    }

    /**
     * Top expensive sellers
     * GET /api/bigdata/cost/top-sellers?tenant_id=1&limit=20
     */
    public function topSellers(Request $request): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 1);
        $limit = (int) $request->input('limit', 20);

        return response()->json([
            'success' => true,
            'data' => array_map(
                fn($dto) => $dto->toArray(),
                $this->costFacade->getTopExpensiveSellers($tenantId, $limit),
            ),
        ]);
    }

    /**
     * Monthly prediction
     * GET /api/bigdata/cost/predict
     */
    public function predict(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->costFacade->predictMonthly()->toArray(),
        ]);
    }

    /**
     * Optimization recommendations
     * GET /api/bigdata/cost/recommendations
     */
    public function recommendations(): JsonResponse
    {
        $recs = $this->costFacade->optimizeRecommendations();

        return response()->json([
            'success' => true,
            'data' => array_map(fn($r) => $r->toArray(), $recs),
            'total_savings_potential' => round(array_sum(array_map(fn($r) => $r->estimatedSavingsUsd, $recs)), 2),
        ]);
    }

    /**
     * Trigger auto-optimization
     * POST /api/bigdata/cost/auto-optimize
     */
    public function autoOptimize(): JsonResponse
    {
        $results = $this->costFacade->autoOptimize();

        return response()->json([
            'success' => true,
            'data' => $results,
            'applied' => count(array_filter($results)),
            'skipped' => count(array_filter($results, fn($v) => !$v)),
        ]);
    }

    /**
     * ClickHouse cost deep dive
     * GET /api/bigdata/cost/clickhouse?date=2026-04-29
     */
    public function clickhouseCost(Request $request): JsonResponse
    {
        $date = CarbonImmutable::parse($request->input('date', 'today'));

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $this->costFacade->getClickHouseCostMetrics($date),
                'compression_ratios' => $this->costFacade->getCompressionRatios(),
                'top_expensive_queries' => $this->costFacade->getTopExpensiveQueries($date),
                'mv_roi' => $this->costFacade->getMaterializedViewROI(),
            ],
        ]);
    }

    /**
     * Kafka cost
     * GET /api/bigdata/cost/kafka?date=2026-04-29
     */
    public function kafkaCost(Request $request): JsonResponse
    {
        $date = CarbonImmutable::parse($request->input('date', 'today'));

        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getKafkaCostMetrics($date),
        ]);
    }

    /**
     * Spark/ML cost
     * GET /api/bigdata/cost/spark-ml?days=30
     */
    public function sparkMLCost(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $endDate = CarbonImmutable::now();
        $startDate = $endDate->subDays($days);

        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getSparkMLCostMetrics($startDate, $endDate),
        ]);
    }

    /**
     * Unit economics
     * GET /api/bigdata/cost/unit-economics?days=30
     */
    public function unitEconomics(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getUnitEconomics($days),
        ]);
    }

    /**
     * Anomalies
     * GET /api/bigdata/cost/anomalies
     */
    public function anomalies(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->costFacade->getOpenAnomalies(),
        ]);
    }

    /**
     * Detect anomalies (run detection)
     * POST /api/bigdata/cost/detect-anomalies
     */
    public function detectAnomalies(): JsonResponse
    {
        $anomalies = $this->costFacade->detectAnomalies();

        return response()->json([
            'success' => true,
            'detected' => count($anomalies),
            'data' => array_map(fn($a) => $a->toNotification(), $anomalies),
        ]);
    }

    /**
     * Prometheus metrics scrape endpoint
     * GET /metrics/bigdata-cost
     */
    public function metrics(): \Illuminate\Http\Response
    {
        return new \Illuminate\Http\Response(
            $this->exporter->render(),
            200,
            ['Content-Type' => $this->exporter->getContentType()],
        );
    }
}
