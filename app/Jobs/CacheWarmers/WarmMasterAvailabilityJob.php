<?php declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;

/**
 * Class WarmMasterAvailabilityJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see \Illuminate\Contracts\Queue\ShouldQueue
 * @package App\Jobs\CacheWarmers
 */
final class WarmMasterAvailabilityJob implements ShouldQueue
{
        protected int $tries = 3;
        protected int $timeout = 30;

        public function __construct(private readonly int $masterId,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,
    ) {}

        public function handle(): void
        {
            try {
                $cacheKey = "master_availability:{$this->masterId}";
                $cacheTag = "master_availability_{$this->masterId}";

                $availability = $this->getAvailableSlots();

                $this->cache->store('redis')
                    ->tags([$cacheTag])
                    ->put($cacheKey, $availability, now()->addHours(2));

                $this->logger->channel('audit')->info('Master availability cached', [
                    'master_id' => $this->masterId,
                    'slots_count' => count($availability['slots'] ?? []),
                ]);
            } catch (\Throwable $e) {
                $this->logger->channel('audit')->error('Failed to warm master availability cache', [
                    'master_id' => $this->masterId,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        private function getAvailableSlots(): array
        {
            return [
                'master_id' => $this->masterId,
                'slots' => [],
                'warmed_at' => now()->toIso8601String(),
                'correlation_id' => \Illuminate\Support\Str::uuid()->toString(),
            ];
        }
}
