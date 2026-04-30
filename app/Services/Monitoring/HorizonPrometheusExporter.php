<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use Carbon\CarbonImmutable;

/**
 * HorizonPrometheusExporter - Exports Horizon queue metrics for Prometheus scraping
 *
 * CRITICAL: Provides observability for queue layer health
 * - Exports metrics for all queues (processed, failed, waiting, runtime)
 * - Exports supervisor metrics (processes, memory, status)
 * - Exports job metrics by type and status
 * - Metrics endpoint at /metrics/horizon for Prometheus scraping
 *
 * Metrics exposed:
 * - horizon_jobs_processed_total{queue, status}
 * - horizon_jobs_waiting{queue}
 * - horizon_jobs_failed_total{queue}
 * - horizon_job_runtime_seconds{queue, job_class}
 * - horizon_supervisor_processes{supervisor}
 * - horizon_supervisor_memory_mb{supervisor}
 * - horizon_supervisor_status{supervisor, status}
 *
 * CatVRF 2026 - Production Ready
 */
final class HorizonPrometheusExporter
{
    private readonly ?object $jobRepository = null;

    private readonly ?object $supervisorRepository = null;

    private readonly ?object $metricsRepository = null;

    public function __construct()
    {
        if (class_exists('Laravel\Horizon\Horizon')) {
            $this->jobRepository = app('Laravel\Horizon\Contracts\JobRepository');
            $this->supervisorRepository = app('Laravel\Horizon\Contracts\SupervisorRepository');
            $this->metricsRepository = app('Laravel\Horizon\Contracts\MetricsRepository');
        }
    }

    /**
     * Export all Horizon metrics in Prometheus format
     */
    public function export(): string
    {
        $metrics = [];

        // Queue metrics
        $metrics[] = $this->exportQueueMetrics();

        // Supervisor metrics
        $metrics[] = $this->exportSupervisorMetrics();

        // Job metrics by type
        $metrics[] = $this->exportJobTypeMetrics();

        return implode("\n", array_filter($metrics));
    }

    /**
     * Export metrics for HTTP response
     */
    public function exportForHttp(): string
    {
        $metrics = $this->export();

        // Add help text for Prometheus
        $output = "# HELP horizon_jobs_processed_total Total number of jobs processed by queue and status\n";
        $output .= "# TYPE horizon_jobs_processed_total counter\n";
        $output .= "# HELP horizon_jobs_waiting Number of jobs waiting in queue\n";
        $output .= "# TYPE horizon_jobs_waiting gauge\n";
        $output .= "# HELP horizon_jobs_failed_total Total number of failed jobs by queue\n";
        $output .= "# TYPE horizon_jobs_failed_total counter\n";
        $output .= "# HELP horizon_job_runtime_seconds_avg Average job runtime in seconds\n";
        $output .= "# TYPE horizon_job_runtime_seconds_avg gauge\n";
        $output .= "# HELP horizon_jobs_throughput_per_minute Jobs processed per minute\n";
        $output .= "# TYPE horizon_jobs_throughput_per_minute gauge\n";
        $output .= "# HELP horizon_supervisor_processes Number of processes per supervisor\n";
        $output .= "# TYPE horizon_supervisor_processes gauge\n";
        $output .= "# HELP horizon_supervisor_memory_mb Memory usage per supervisor in MB\n";
        $output .= "# TYPE horizon_supervisor_memory_mb gauge\n";
        $output .= "# HELP horizon_supervisor_status Supervisor status (1=running, 0=stopped)\n";
        $output .= "# TYPE horizon_supervisor_status gauge\n";
        $output .= "# HELP horizon_supervisor_uptime_seconds Supervisor uptime in seconds\n";
        $output .= "# TYPE horizon_supervisor_uptime_seconds gauge\n";
        $output .= "# HELP horizon_jobs_by_type_total Total jobs by type and queue\n";
        $output .= "# TYPE horizon_jobs_by_type_total counter\n";
        $output .= "# HELP horizon_jobs_by_type_failed Failed jobs by type and queue\n";
        $output .= "# TYPE horizon_jobs_by_type_failed counter\n";
        $output .= "# HELP horizon_job_runtime_seconds Job runtime in seconds\n";
        $output .= "# TYPE horizon_job_runtime_seconds gauge\n";
        $output .= "\n";
        $output .= $metrics;

        return $output;
    }

    /**
     * Export queue-level metrics
     */
    private function exportQueueMetrics(): string
    {
        $lines = [];
        $queues = config('horizon.environments.'.config('app.env').'.*.queue', []);

        // Get all unique queues from current environment
        $allQueues = $this->getAllQueues();

        foreach ($allQueues as $queue) {
            // Jobs processed by status
            $stats = $this->metricsRepository->snapshot($queue, CarbonImmutable::now()->subHours(24), CarbonImmutable::now());

            $lines[] = sprintf(
                'horizon_jobs_processed_total{queue="%s",status="completed"} %d',
                $queue,
                $stats['completed'] ?? 0
            );

            $lines[] = sprintf(
                'horizon_jobs_processed_total{queue="%s",status="failed"} %d',
                $queue,
                $stats['failed'] ?? 0
            );

            // Jobs waiting
            $waiting = $this->jobRepository->countPending($queue);
            $lines[] = sprintf(
                'horizon_jobs_waiting{queue="%s"} %d',
                $queue,
                $waiting
            );

            // Jobs failed
            $failed = $this->jobRepository->countFailed($queue);
            $lines[] = sprintf(
                'horizon_jobs_failed_total{queue="%s"} %d',
                $queue,
                $failed
            );

            // Average job runtime
            $runtime = $this->metricsRepository->runtime($queue);
            $lines[] = sprintf(
                'horizon_job_runtime_seconds_avg{queue="%s"} %.2f',
                $queue,
                $runtime ?? 0
            );

            // Job throughput (jobs per minute)
            $throughput = $this->metricsRepository->throughput($queue);
            $lines[] = sprintf(
                'horizon_jobs_throughput_per_minute{queue="%s"} %.2f',
                $queue,
                $throughput ?? 0
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Export supervisor metrics
     */
    private function exportSupervisorMetrics(): string
    {
        $lines = [];
        $supervisors = $this->supervisorRepository->all();

        foreach ($supervisors as $supervisor) {
            $name = $supervisor->name ?? 'unknown';

            // Process count
            $lines[] = sprintf(
                'horizon_supervisor_processes{supervisor="%s"} %d',
                $name,
                $supervisor->processes ?? 0
            );

            // Memory usage
            $lines[] = sprintf(
                'horizon_supervisor_memory_mb{supervisor="%s"} %d',
                $name,
                $supervisor->memory ?? 0
            );

            // Status
            $status = $supervisor->status ?? 'unknown';
            $statusValue = $status === 'running' ? 1 : 0;
            $lines[] = sprintf(
                'horizon_supervisor_status{supervisor="%s",status="%s"} %d',
                $name,
                $status,
                $statusValue
            );

            // Uptime
            if (isset($supervisor->started_at)) {
                $uptime = CarbonImmutable::now()->diffInSeconds($supervisor->started_at);
                $lines[] = sprintf(
                    'horizon_supervisor_uptime_seconds{supervisor="%s"} %d',
                    $name,
                    $uptime
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Export job metrics by type
     */
    private function exportJobTypeMetrics(): string
    {
        $lines = [];

        // Get recent jobs grouped by type
        $recentJobs = $this->jobRepository->getRecent(1000);
        $jobStats = [];

        foreach ($recentJobs as $job) {
            $jobClass = $job->payload['data']['commandName'] ?? 'unknown';
            $queue = $job->queue ?? 'default';
            $status = $job->status ?? 'pending';

            $key = "{$jobClass}:{$queue}";

            if (! isset($jobStats[$key])) {
                $jobStats[$key] = [
                    'class' => $jobClass,
                    'queue' => $queue,
                    'total' => 0,
                    'failed' => 0,
                    'runtime' => [],
                ];
            }

            $jobStats[$key]['total']++;

            if ($status === 'failed') {
                $jobStats[$key]['failed']++;
            }

            if (isset($job->reserved_at) && isset($job->available_at)) {
                $runtime = $job->reserved_at - $job->available_at;
                $jobStats[$key]['runtime'][] = $runtime;
            }
        }

        // Export job type metrics
        foreach ($jobStats as $stat) {
            $lines[] = sprintf(
                'horizon_jobs_by_type_total{job_class="%s",queue="%s"} %d',
                $stat['class'],
                $stat['queue'],
                $stat['total']
            );

            $lines[] = sprintf(
                'horizon_jobs_by_type_failed{job_class="%s",queue="%s"} %d',
                $stat['class'],
                $stat['queue'],
                $stat['failed']
            );

            if (! empty($stat['runtime'])) {
                $avgRuntime = array_sum($stat['runtime']) / count($stat['runtime']);
                $lines[] = sprintf(
                    'horizon_job_runtime_seconds{job_class="%s",queue="%s"} %.2f',
                    $stat['class'],
                    $stat['queue'],
                    $avgRuntime
                );
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get all queues from current environment configuration
     */
    private function getAllQueues(): array
    {
        $environment = config('app.env');
        $config = config("horizon.environments.{$environment}", []);

        $queues = [];

        foreach ($config as $supervisorName => $supervisorConfig) {
            if (isset($supervisorConfig['queue'])) {
                $supervisorQueues = (array) $supervisorConfig['queue'];
                $queues = array_merge($queues, $supervisorQueues);
            }
        }

        return array_unique($queues);
    }
}
