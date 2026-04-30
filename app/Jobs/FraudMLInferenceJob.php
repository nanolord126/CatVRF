<?php

declare(strict_types=1);

namespace App\Jobs;

use Psr\Log\LoggerInterface;

use App\Services\Fraud\MLInferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Log\LogManager;
use Carbon\CarbonImmutable;

/**
 * Async ML Inference Job for Fraud Detection
 *
 * Processes fraud scoring asynchronously to avoid blocking
 * the main request flow. Results are cached for immediate retrieval.
 */
final class FraudMLInferenceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [5, 10, 30]; // Exponential backoff

    public int $timeout = 10;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly array $features,
        private readonly string $correlationId,
        private readonly string $cacheKey,
        private readonly Repository $cache,
        private readonly LogManager $log,) {
        $this->onQueue('ml-recalculate');
    }

    public function handle(MLInferenceService $mlInference): void
    {
        $score = $mlInference->predict($this->features, $this->correlationId);

        // Cache result for 5 minutes
        $this->cache->put($this->cacheKey, [
            'score' => $score,
            'correlation_id' => $this->correlationId,
            'computed_at' => CarbonImmutable::now()->toIso8601String(),
        ], 300);

        $this->log->channel('fraud_alert')->$this->logger->info('Fraud ML inference completed', [
            'correlation_id' => $this->correlationId,
            'score' => $score,
            'cache_key' => $this->cacheKey,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->log->channel('fraud_alert')->error('Fraud ML inference job failed', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Cache fallback score on failure
        $this->cache->put($this->cacheKey, [
            'score' => 0.5, // Neutral score on failure
            'correlation_id' => $this->correlationId,
            'computed_at' => CarbonImmutable::now()->toIso8601String(),
            'error' => $exception->getMessage(),
        ], 60);
    }
}
