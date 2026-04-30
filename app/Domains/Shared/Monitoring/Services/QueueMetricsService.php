<?php

declare(strict_types=1);

namespace App\Domains\Shared\Monitoring\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

final readonly class QueueMetricsService
{
    private const CACHE_TTL = 60; // 1 minute

    public function registerMetrics(): void
    {
        // This would integrate with Prometheus registry
        // For production, use spatie/laravel-prometheus
        $registry = app(\Prometheus\CollectorRegistry::class);

        $registry->getOrRegisterGauge(
            'horizon',
            'jobs_pending',
            'Pending jobs by queue',
            ['queue']
        );

        $registry->getOrRegisterGauge(
            'horizon',
            'jobs_failed',
            'Failed jobs by queue',
            ['queue']
        );

        $registry->getOrRegisterGauge(
            'horizon',
            'jobs_processed',
            'Processed jobs total',
            ['queue']
        );

        $registry->getOrRegisterGauge(
            'horizon',
            'average_wait_time',
            'Average wait time in seconds',
            ['queue']
        );

        // Supermarket specific metrics
        $registry->getOrRegisterGauge(
            'supermarket',
            'cold_chain_jobs',
            'Cold chain orders in queue'
        );

        $registry->getOrRegisterGauge(
            'supermarket',
            'b2b_orders_pending',
            'B2B orders waiting'
        );
    }

    public function updateMetrics(): array
    {
        $cacheKey = 'queue_metrics_' . now()->format('Y-m-d-H-i');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $queues = ['supermarket-high', 'default', 'crm-sync', 'notifications', 'supermarket'];
            $metrics = [];

            foreach ($queues as $queue) {
                $metrics[$queue] = $this->getQueueMetrics($queue);
            }

            // Supermarket specific metrics
            $metrics['supermarket_specific'] = [
                'cold_chain_jobs' => $this->getColdChainJobsCount(),
                'b2b_orders_pending' => $this->getB2BOrdersPendingCount(),
                'avg_processing_time' => $this->getAverageProcessingTime(),
            ];

            // Health status
            $metrics['health'] = $this->getQueueHealth($metrics);

            Log::debug('Queue metrics updated', $metrics);

            return $metrics;
        });
    }

    private function getQueueMetrics(string $queue): array
    {
        $pending = DB::table('jobs')->where('queue', $queue)->count();
        $failed = DB::table('failed_jobs')->where('queue', $queue)->count();
        $reserved = DB::table('jobs')->where('queue', $queue)->whereNotNull('reserved_at')->count();

        // Calculate processed in last hour
        $processedLastHour = DB::table('job_batches')
            ->where('created_at', '>=', now()->subHour())
            ->whereJsonContains('options->queue', $queue)
            ->count();

        return [
            'pending' => $pending,
            'failed' => $failed,
            'reserved' => $reserved,
            'processed_last_hour' => $processedLastHour,
            'total' => $pending + $reserved,
        ];
    }

    private function getColdChainJobsCount(): int
    {
        return DB::table('jobs')
            ->where('queue', 'supermarket-high')
            ->where('created_at', '>=', now()->subHours(2))
            ->count();
    }

    private function getB2BOrdersPendingCount(): int
    {
        return DB::table('supermarket_orders')
            ->where('is_b2b', true)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(4))
            ->count();
    }

    private function getAverageProcessingTime(): float
    {
        $avgTime = DB::table('job_batches')
            ->where('finished_at', '>=', now()->subHour())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, finished_at)) as avg_time')
            ->value('avg_time');

        return (float) ($avgTime ?? 0);
    }

    private function getQueueHealth(array $metrics): array
    {
        $health = [];

        foreach ($metrics as $queue => $data) {
            if ($queue === 'health' || $queue === 'supermarket_specific') {
                continue;
            }

            $health[$queue] = [
                'status' => $this->calculateHealthStatus($data),
                'score' => $this->calculateHealthScore($data),
            ];
        }

        return $health;
    }

    private function calculateHealthStatus(array $metrics): string
    {
        $pending = $metrics['pending'] ?? 0;
        $failed = $metrics['failed'] ?? 0;

        if ($pending > 500 || $failed > 50) {
            return 'critical';
        }

        if ($pending > 200 || $failed > 20) {
            return 'warning';
        }

        return 'healthy';
    }

    private function calculateHealthScore(array $metrics): int
    {
        $pending = $metrics['pending'] ?? 0;
        $failed = $metrics['failed'] ?? 0;

        $score = 100;

        // Deduct points for pending jobs
        $score -= min($pending / 10, 30);

        // Deduct points for failed jobs
        $score -= min($failed * 2, 40);

        return max(0, (int) $score);
    }

    public function getHorizonStats(): array
    {
        // Integrate with Laravel Horizon if available
        if (class_exists(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)) {
            $repository = app(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class);
            $supervisors = $repository->all();

            return [
                'total_supervisors' => count($supervisors),
                'active_supervisors' => count(array_filter($supervisors, fn($s) => $s->status === 'running')),
                'total_workers' => array_sum(array_map(fn($s) => $s->workers, $supervisors)),
                'processes' => $supervisors,
            ];
        }

        // Fallback to basic metrics
        return [
            'total_supervisors' => 0,
            'active_supervisors' => 0,
            'total_workers' => 0,
            'processes' => [],
        ];
    }

    public function getAlerts(): array
    {
        $metrics = $this->updateMetrics();
        $alerts = [];

        foreach ($metrics['health'] ?? [] as $queue => $health) {
            if ($health['status'] === 'critical') {
                $alerts[] = [
                    'queue' => $queue,
                    'severity' => 'critical',
                    'message' => "Queue {$queue} is in critical state",
                    'score' => $health['score'],
                    'timestamp' => now()->toIso8601String(),
                ];
            } elseif ($health['status'] === 'warning') {
                $alerts[] = [
                    'queue' => $queue,
                    'severity' => 'warning',
                    'message' => "Queue {$queue} has warning status",
                    'score' => $health['score'],
                    'timestamp' => now()->toIso8601String(),
                ];
            }
        }

        return $alerts;
    }

    public function exportMetrics(string $format = 'json'): string
    {
        $metrics = $this->updateMetrics();

        return match ($format) {
            'json' => json_encode($metrics, JSON_PRETTY_PRINT),
            'prometheus' => $this->toPrometheusFormat($metrics),
            default => json_encode($metrics),
        };
    }

    private function toPrometheusFormat(array $metrics): string
    {
        $output = [];

        foreach ($metrics as $queue => $data) {
            if ($queue === 'health' || $queue === 'supermarket_specific') {
                continue;
            }

            $output[] = sprintf(
                'horizon_jobs_pending{queue="%s"} %d',
                $queue,
                $data['pending'] ?? 0
            );

            $output[] = sprintf(
                'horizon_jobs_failed{queue="%s"} %d',
                $queue,
                $data['failed'] ?? 0
            );

            $output[] = sprintf(
                'horizon_jobs_reserved{queue="%s"} %d',
                $queue,
                $data['reserved'] ?? 0
            );
        }

        return implode("\n", $output) . "\n";
    }
}
