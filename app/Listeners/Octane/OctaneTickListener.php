<?php declare(strict_types=1);

namespace App\Listeners\Octane;



use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Laravel\Octane\Events\TickReceived;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final class OctaneTickListener
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    private int $tickCount = 0;
    private int $lastSecond = 0;

    public function handle(TickReceived $event): void
    {
        $this->tickCount++;
        $now = time();

        // Every 5 seconds
        if ($now - $this->lastSecond >= 5) {
            $this->checkJobQueue();
            $this->cleanupExpiredCache();
            $this->lastSecond = $now;
        }

        // Every 30 seconds
        if ($this->tickCount % 60 === 0) {
            $this->reportMetrics();
        }
    }

    private function checkJobQueue(): void
    {
        // Trigger job processing if queue is pending
        try {
            if ($this->db->table('jobs')->count() > 0) {
                $this->logger->channel('octane')->debug('Job queue processed', [
                    'pending_jobs' => $this->db->table('jobs')->count(),
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->channel('octane')->error('Job queue check failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function cleanupExpiredCache(): void
    {
        // Cleanup Redis keys with TTL
        try {
            $redis = \Redis::connection();
            // Existing TTL keys are auto-expired by Redis
            $info = $redis->info('memory');

            if ($info['used_memory'] > ($this->config->get('cache.default') === 'redis' ? 512 * 1024 * 1024 : 100 * 1024 * 1024)) {
                $redis->flushdb();
                $this->logger->channel('octane')->warning('Cache flushed due to memory pressure');
            }
        } catch (\Exception $e) {
            $this->logger->channel('octane')->error('Cache cleanup failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function reportMetrics(): void
    {
        // Log memory and performance metrics
        try {
            $memoryUsage = memory_get_usage(true) / 1024 / 1024;
            $peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

            $this->logger->channel('octane')->debug('Octane metrics', [
                'memory_mb' => round($memoryUsage, 2),
                'peak_memory_mb' => round($peakMemory, 2),
                'tick_count' => $this->tickCount,
            ]);
        } catch (\Exception $e) {
            $this->logger->channel('octane')->error('Metrics reporting failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
