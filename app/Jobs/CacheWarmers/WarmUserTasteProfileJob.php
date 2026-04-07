<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class WarmUserTasteProfileJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs\CacheWarmers
 */
final class WarmUserTasteProfileJob implements ShouldQueue
{
    use Queueable;

        protected int $tries = 3;
        protected int $timeout = 30;

        public function __construct(private readonly int $userId,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

        public function handle(): void
        {
            try {
                $cacheKey = "user_taste_profile_{$this->userId}";
                $cacheTag = "user_taste_{$this->userId}";

                $profile = $this->calculateTasteProfile();

                $this->cache->store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $profile, now()->addHours(6));

                $this->logger->channel('audit')->info('User taste profile cached', [
                    'user_id' => $this->userId,
                    'correlation_id' => $profile['correlation_id'] ?? null,
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to warm user taste cache', [
                    'user_id' => $this->userId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function calculateTasteProfile(): array
        {
            return [
                'user_id' => $this->userId,
                'categories' => [],
                'price_range' => 'mid',
                'preferred_brands' => [],
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
                'analyzed_at' => now()->toIso8601String(),
            ];
        }
}
