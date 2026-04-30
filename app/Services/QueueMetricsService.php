<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

final class QueueMetricsService
{
    public function updateMetrics(): void
    {
        $metrics = [
            'supermarket' => $this->getQueueMetrics('supermarket'),
            'supermarket-high' => $this->getQueueMetrics('supermarket-high'),
            'supermarket-low' => $this->getQueueMetrics('supermarket-low'),
            'crm-sync' => $this->getQueueMetrics('crm-sync'),
        ];

        foreach ($metrics as $queue => $data) {
            Cache::put("queue:{$queue}:metrics", $data, now()->addMinutes(5));
        }

        $this->publishToRedis($metrics);
    }

    public function getQueueMetrics(string $queue): array
    {
        try {
            $stats = Redis::connection('default')->connection()->info('stats');
            
            $pending = DB::table('jobs')
                ->where('queue', $queue)
                ->count();

            $failed = DB::table('failed_jobs')
                ->where('queue', $queue)
                ->count();

            $completed = DB::table('job_batches')
                ->where('queue', $queue)
                ->where('finished_at', '>=', now()->subHour())
                ->count();

            $processing = $this->getProcessingCount($queue);

            $avgProcessingTime = $this->getAvgProcessingTime($queue);

            return [
                'queue' => $queue,
                'pending' => $pending,
                'processing' => $processing,
                'completed_last_hour' => $completed,
                'failed' => $failed,
                'total_jobs' => $pending + $processing + $completed + $failed,
                'avg_processing_time_ms' => $avgProcessingTime,
                'timestamp' => now()->toIso8601String(),
            ];
        } catch (\Exception $e) {
            return [
                'queue' => $queue,
                'pending' => 0,
                'processing' => 0,
                'completed_last_hour' => 0,
                'failed' => 0,
                'total_jobs' => 0,
                'avg_processing_time_ms' => 0,
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getProcessingCount(string $queue): int
    {
        try {
            return DB::table('jobs')
                ->where('queue', $queue)
                ->where('reserved_at', '>=', now()->subMinutes(5))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAvgProcessingTime(string $queue): float
    {
        try {
            $avg = DB::table('job_batches')
                ->where('queue', $queue)
                ->whereNotNull('finished_at')
                ->where('finished_at', '>=', now()->subHour())
                ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, created_at, finished_at) / 1000) as avg_ms')
                ->value('avg_ms');

            return (float) ($avg ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function publishToRedis(array $metrics): void
    {
        try {
            foreach ($metrics as $queue => $data) {
                Redis::connection('default')->publish(
                    "metrics:queue:{$queue}",
                    json_encode($data)
                );
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    public function getHealthStatus(string $queue): array
    {
        $metrics = Cache::get("queue:{$queue}:metrics", $this->getQueueMetrics($queue));
        
        $health = 'healthy';
        $issues = [];

        if ($metrics['pending'] > 1000) {
            $health = 'warning';
            $issues[] = 'High pending jobs';
        }

        if ($metrics['failed'] > 10) {
            $health = 'critical';
            $issues[] = 'High failed jobs';
        }

        if ($metrics['avg_processing_time_ms'] > 30000) {
            $health = 'warning';
            $issues[] = 'Slow processing';
        }

        return [
            'queue' => $queue,
            'health' => $health,
            'issues' => $issues,
            'metrics' => $metrics,
        ];
    }
}
