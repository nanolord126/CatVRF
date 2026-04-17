<?php declare(strict_types=1);

namespace App\Jobs;

use App\Services\Fraud\MLInferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Async ML Inference Job for Fraud Detection
 * 
 * Processes fraud scoring asynchronously to avoid blocking
 * the main request flow. Results are cached for immediate retrieval.
 */
final class FraudMLInferenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = [5, 10, 30]; // Exponential backoff
    public int $timeout = 10;

    public function __construct(
        private readonly array $features,
        private readonly string $correlationId,
        private readonly string $cacheKey,
    ) {
        $this->onQueue('fraud-ml-inference');
    }

    public function handle(MLInferenceService $mlInference): void
    {
        $score = $mlInference->predict($this->features, $this->correlationId);

        // Cache result for 5 minutes
        Cache::put($this->cacheKey, [
            'score' => $score,
            'correlation_id' => $this->correlationId,
            'computed_at' => now()->toIso8601String(),
        ], 300);

        Log::channel('fraud_alert')->info('Fraud ML inference completed', [
            'correlation_id' => $this->correlationId,
            'score' => $score,
            'cache_key' => $this->cacheKey,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('fraud_alert')->error('Fraud ML inference job failed', [
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Cache fallback score on failure
        Cache::put($this->cacheKey, [
            'score' => 0.5, // Neutral score on failure
            'correlation_id' => $this->correlationId,
            'computed_at' => now()->toIso8601String(),
            'error' => $exception->getMessage(),
        ], 60);
    }
}
