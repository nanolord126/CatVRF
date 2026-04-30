<?php

declare(strict_types=1);

namespace App\Octane\Services;

use Illuminate\Log\LogManager;
use Prometheus\CollectorRegistry;
use Prometheus\Gauge;
use Prometheus\Counter;
use Laravel\Octane\Facades\Octane;
use Swoole\Coroutine;

final readonly class PrometheusSwooleExporter
{
    private readonly Gauge $workerCountGauge;

    private readonly Gauge $coroutineCountGauge;

    private readonly Gauge $tableMemoryGauge;

    private readonly Gauge $tableCountGauge;

    private readonly Counter $requestCounter;

    private readonly Gauge $requestLatencyGauge;

    public function __construct(
        private readonly CollectorRegistry $registry,
        private readonly SwooleTableService $tableService
    ) {
        $this->initMetrics();
    }

    public function collectMetrics(): void
    {
        if (! function_exists('swoole_server')) {
            return;
        }

        try {
            // Get server stats if available
            $server = Octane::server();

            if ($server && method_exists($server, 'stats')) {
                $stats = $server->stats();

                if (isset($stats['worker_num'])) {
                    $this->workerCountGauge->set($stats['worker_num'], ['http']);
                }

                if (isset($stats['task_worker_num'])) {
                    $this->workerCountGauge->set($stats['task_worker_num'], ['task']);
                }
            }

            // Coroutine count
            if (function_exists('Swoole\Coroutine::stats')) {
                $coroutineStats = Coroutine::stats();
                if (isset($coroutineStats['coroutine_num'])) {
                    $this->coroutineCountGauge->set($coroutineStats['coroutine_num']);
                }
            }

            // Table metrics
            $tableStats = $this->tableService->getStats();
            foreach ($tableStats as $tableName => $stats) {
                $this->tableMemoryGauge->set($stats['memory_size'], [$tableName]);
                $this->tableCountGauge->set($stats['count'], [$tableName]);
            }

        } catch (\Throwable $e) {
            $this->log->error('Failed to collect Swoole metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function recordRequest(string $method, int $status, float $latencyMs, string $endpoint): void
    {
        $this->requestCounter->inc([$method, (string) $status]);
        $this->requestLatencyGauge->set($latencyMs, [$endpoint]);
    }

    public function getMetricsText(): string
    {
        $this->collectMetrics();

        return $this->registry->getMetricFamilySamples();
    }

    private function initMetrics(): void
    {
        $this->workerCountGauge = $this->registry->getOrRegisterGauge(
            'octane',
            'swoole_worker_count',
            'Number of Swoole workers',
            ['type']
        );

        $this->coroutineCountGauge = $this->registry->getOrRegisterGauge(
            'octane',
            'swoole_coroutine_count',
            'Number of active coroutines'
        );

        $this->tableMemoryGauge = $this->registry->getOrRegisterGauge(
            'octane',
            'swoole_table_memory_bytes',
            'Memory usage of Swoole tables',
            ['table_name']
        );

        $this->tableCountGauge = $this->registry->getOrRegisterGauge(
            'octane',
            'swoole_table_count',
            'Number of records in Swoole tables',
            ['table_name']
        );

        $this->requestCounter = $this->registry->getOrRegisterCounter(
            'octane',
            'swoole_requests_total',
            'Total number of requests handled by Swoole',
            ['method', 'status']
        );

        $this->requestLatencyGauge = $this->registry->getOrRegisterGauge(
            'octane',
            'swoole_request_latency_ms',
            'Request latency in milliseconds',
            ['endpoint']
        );
    }
}
