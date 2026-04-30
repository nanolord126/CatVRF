<?php

declare(strict_types=1);

namespace Modules\BigData\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BigData\Application\Jobs\MaintenanceJob;
use Modules\BigData\Application\Jobs\SelfHealJob;
use Modules\BigData\Application\Services\BigDataMonitoringFacade;

/**
 * Monitoring Controller
 *
 * REST API for BigData monitoring operations.
 * Routes:
 *   GET  /api/bigdata/monitoring/snapshot     — Full monitoring snapshot
 *   GET  /api/bigdata/monitoring/pipeline      — Pipeline health
 *   GET  /api/bigdata/monitoring/freshness     — Data freshness
 *   GET  /api/bigdata/monitoring/clv-drift      — CLV model drift
 *   GET  /api/bigdata/monitoring/query-perf     — Query performance
 *   GET  /api/bigdata/monitoring/alerts         — Active alerts
 *   POST /api/bigdata/monitoring/self-heal       — Trigger self-healing
 *   POST /api/bigdata/monitoring/maintenance     — Trigger maintenance
 */
final class MonitoringController extends Controller
{
    public function __construct(
        private readonly BigDataMonitoringFacade $monitoring,
    ) {}

    /**
     * Get comprehensive monitoring snapshot
     */
    public function snapshot(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'data' => $this->monitoring->getSnapshot(),
        ]);
    }

    /**
     * Get pipeline health
     */
    public function pipeline(Request $request): JsonResponse
    {
        $topic = $request->query('topic', 'bigdata_events');
        $health = $this->monitoring->getPipelineHealth($topic);

        return response()->json([
            'status' => 'ok',
            'data' => $health->toArray(),
        ]);
    }

    /**
     * Get data freshness for one or all tables
     */
    public function freshness(Request $request): JsonResponse
    {
        $table = $request->query('table');

        if ($table) {
            $data = $this->monitoring->getDataFreshness($table)->toArray();
        } else {
            $all = $this->monitoring->getAllDataFreshness();
            $data = array_map(fn($f) => $f->toArray(), $all);
        }

        return response()->json([
            'status' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * Get CLV model drift
     */
    public function clvDrift(): JsonResponse
    {
        $drift = $this->monitoring->getCLVModelDrift();

        return response()->json([
            'status' => 'ok',
            'data' => $drift->toArray(),
        ]);
    }

    /**
     * Get query performance
     */
    public function queryPerformance(Request $request): JsonResponse
    {
        $queryName = $request->query('query', 'seller_dashboard');
        $perf = $this->monitoring->getQueryPerformance($queryName);

        return response()->json([
            'status' => 'ok',
            'data' => $perf->toArray(),
        ]);
    }

    /**
     * Get active alerts
     */
    public function alerts(): JsonResponse
    {
        $alerts = $this->monitoring->evaluateAlerts();
        $data = array_map(fn($a) => $a->toArray(), $alerts);

        $firingCount = count(array_filter($alerts, fn($a) => $a->firing));

        return response()->json([
            'status' => 'ok',
            'firing_count' => $firingCount,
            'total_count' => count($alerts),
            'data' => $data,
        ]);
    }

    /**
     * Trigger self-healing (restart consumers if needed)
     */
    public function selfHeal(Request $request): JsonResponse
    {
        $topic = $request->input('topic', 'bigdata_events');
        $correlationId = $request->header('X-Correlation-ID', uniqid('sh_', true));

        dispatch(new SelfHealJob($topic, $correlationId));

        return response()->json([
            'status' => 'accepted',
            'message' => 'Self-heal job dispatched',
            'topic' => $topic,
            'correlation_id' => $correlationId,
        ], 202);
    }

    /**
     * Trigger ClickHouse maintenance
     */
    public function maintenance(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID', uniqid('mnt_', true));

        dispatch(new MaintenanceJob($correlationId));

        return response()->json([
            'status' => 'accepted',
            'message' => 'Maintenance job dispatched',
            'correlation_id' => $correlationId,
        ], 202);
    }
}
