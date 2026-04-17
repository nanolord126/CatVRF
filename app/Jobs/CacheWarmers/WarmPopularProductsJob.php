<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class WarmPopularProductsJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs\CacheWarmers
 */
final class WarmPopularProductsJob implements ShouldQueue
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
                $cacheKey = "popular_products:{$this->vertical}";
                $cacheTag = "popular_products_{$this->vertical}";

                $popularProducts = $this->getPopularProducts();

                $this->cache->store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $popularProducts, now()->addHours(4));

                $this->logger->channel('audit')->info('Popular products cached', [
                    'vertical' => $this->vertical,
                    'products_count' => count($popularProducts),
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to warm popular products cache', [
                    'vertical' => $this->vertical,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function getPopularProducts(): array
        {
            return [
                'vertical' => $this->vertical,
                'products' => [],
                'warmed_at' => now()->toIso8601String(),
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        }
}
