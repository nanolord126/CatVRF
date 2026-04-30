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
 * Class WarmAIConstructorResultJob
 *
 * Queued job for async processing.
 * Maintains correlation_id for full traceability.
 * Retries and timeout configured per job.
 *
 * @see ShouldQueue
 */
final class WarmAIConstructorResultJob implements ShouldQueue
{
    protected readonly int $tries = 3;

    protected readonly int $timeout = 60;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly int $userId,
        private readonly string $vertical,
        private readonly array $designData,
        private readonly LogManager $logger,
        private readonly CacheManager $cache,) {}

    public function handle(): void
    {
        try {
            $cacheKey = "ai_constructor:user_{$this->userId}:vertical_{$this->vertical}";
            $cacheTag = "ai_constructor_{$this->userId}";

            $result = [
                'user_id' => $this->userId,
                'vertical' => $this->vertical,
                'design_data' => $this->designData,
                'cached_at' => CarbonImmutable::now()->toIso8601String(),
                'correlation_id' => Str::uuid()->toString(),
            ];

            $this->cache->store('redis')
                ->tags([$cacheTag, "ai_constructor_{$this->vertical}"])
                ->put($cacheKey, $result, CarbonImmutable::now()->addHours(12));

            $this->logger->channel('audit')->$this->logger->info('AI constructor result cached', [
                'user_id' => $this->userId,
                'vertical' => $this->vertical,
                'correlation_id' => $result['correlation_id'],
            ]);
        } catch (Exception $e) {
            $this->logger->channel('audit')->error('Failed to cache AI constructor result', [
                'user_id' => $this->userId,
                'vertical' => $this->vertical,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
