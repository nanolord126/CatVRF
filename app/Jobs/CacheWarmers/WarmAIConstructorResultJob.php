<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class WarmAIConstructorResultJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs\CacheWarmers
 */
final class WarmAIConstructorResultJob implements ShouldQueue
{
    use Queueable;

        protected int $tries = 3;
        protected int $timeout = 60;

        public function __construct(
            private readonly int $userId,
            private readonly string $vertical,
            private readonly array $designData,
            private readonly LogManager $logger,
            private readonly CacheManager $cache,
    ) {}

        public function handle(): void
        {
            try {
                $cacheKey = "ai_constructor:user_{$this->userId}:vertical_{$this->vertical}";
                $cacheTag = "ai_constructor_{$this->userId}";

                $result = [
                    'user_id' => $this->userId,
                    'vertical' => $this->vertical,
                    'design_data' => $this->designData,
                    'cached_at' => now()->toIso8601String(),
                    'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                ];

                $this->cache->store('redis')
                    ->tags([$cacheTag, "ai_constructor_{$this->vertical}"])
                    ->put($cacheKey, $result, now()->addHours(12));

                $this->logger->channel('audit')->info('AI constructor result cached', [
                    'user_id' => $this->userId,
                    'vertical' => $this->vertical,
                    'correlation_id' => $result['correlation_id'],
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to cache AI constructor result', [
                    'user_id' => $this->userId,
                    'vertical' => $this->vertical,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
}
