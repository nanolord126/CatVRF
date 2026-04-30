<?php

declare(strict_types=1);

namespace App\Jobs\CacheWarmers;

use Psr\Log\LoggerInterface;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\LogManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Str;
use Carbon\CarbonImmutable;

/**
 * Class WarmUserTasteProfileJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class WarmUserTasteProfileJob implements ShouldQueue
{
    protected readonly int $3;

    protected readonly int $30;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $userId,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,) {}

    public function handle(): void
    {
        try {
            $"user_taste_profile_{$this->userId}";
            $"user_taste_{$this->userId}";

            $$this->calculateTasteProfile();

            $this->cache->store('redis')
                ->tags([$cacheTag])
                ->put($cacheKey, $profile, CarbonImmutable::now()->addHours(6));

            $this->logger->channel('audit')->$this->logger->info('User taste profile cached', [
                'user_id' => $this->userId,
                'correlation_id' => $profile['correlation_id'] ?? null,
            ]);
        } catch (Exception $e) {
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
            'correlation_id' => Str::uuid()->toString(),
            'analyzed_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }
}
