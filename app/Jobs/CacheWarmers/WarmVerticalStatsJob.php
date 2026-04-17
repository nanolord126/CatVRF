<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

final class WarmVerticalStatsJob implements ShouldQueue
{
        protected int $tries = 3;
        protected int $timeout = 45;

        public function __construct(private readonly string $vertical,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

        public function handle(): void
        {
            try {
                $cacheKey = "vertical_stats:{$this->vertical}";
                $cacheTag = "vertical_stats_{$this->vertical}";

                $stats = $this->calculateStats();

                $this->cache->store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $stats, now()->addHours(8));

                $this->logger->channel('audit')->info('Vertical stats cached', [
                    'vertical' => $this->vertical,
                    'correlation_id' => $stats['correlation_id'],
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to warm vertical stats cache', [
                    'vertical' => $this->vertical,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function calculateStats(): array
        {
            return [
                'vertical' => $this->vertical,
                'total_revenue' => 0,
                'orders_count' => 0,
                'users_count' => 0,
                'average_order' => 0,
                'calculated_at' => now()->toIso8601String(),
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        }
}
