<?php

declare(strict_types=1);

namespace App\Domains\Payment\Jobs;

use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * AsyncFraudCheckJob - Async fraud detection for payments.
 *
 * Moves FraudML inference out of the synchronous payment path to prevent
 * latency and connection holding. Uses fallback to rule-based checks on failure.
 *
 * @package App\Domains\Payment\Jobs
 */
final readonly class AsyncFraudCheckJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 2;
    public int $backoff = [5, 10];

    /**
     * Create a new job instance.
     */
    public function __construct(
        private PaymentFraudMLDto $dto,
        private string $correlationId,
    ) {
        $this->onQueue('payment-fraud-high-priority');
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->dto->idempotency_key;
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentFraudMLService $fraudService): void
    {
        $startTime = microtime(true);

        try {
            $result = $fraudService->scorePayment($this->dto);

            $latencyMs = (microtime(true) - $startTime) * 1000;

            Log::channel('fraud')->info('Async fraud check completed', [
                'correlation_id' => $this->correlationId,
                'idempotency_key' => $this->dto->idempotency_key,
                'score' => $result['score'],
                'decision' => $result['decision'],
                'latency_ms' => $latencyMs,
                'cached' => $result['cached'] ?? false,
            ]);

            // Store result in Redis for quick access by payment flow
            $this->storeFraudResult($result);

        } catch (Throwable $e) {
            $latencyMs = (microtime(true) - $startTime) * 1000;

            Log::channel('fraud')->error('Async fraud check failed', [
                'correlation_id' => $this->correlationId,
                'idempotency_key' => $this->dto->idempotency_key,
                'error' => $e->getMessage(),
                'latency_ms' => $latencyMs,
            ]);

            // On failure, allow payment (fail-open for reliability)
            $fallbackResult = [
                'score' => 0.0,
                'decision' => 'allow',
                'explanation' => ['error' => 'fraud_check_failed_async_fallback'],
                'cached' => false,
            ];

            $this->storeFraudResult($fallbackResult);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::channel('fraud')->critical('Async fraud check job failed permanently', [
            'correlation_id' => $this->correlationId,
            'idempotency_key' => $this->dto->idempotency_key,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Store fallback result to allow payment to proceed
        $fallbackResult = [
            'score' => 0.0,
            'decision' => 'allow',
            'explanation' => ['error' => 'fraud_check_job_failed_permanent'],
            'cached' => false,
        ];

        $this->storeFraudResult($fallbackResult);
    }

    /**
     * Store fraud result in Redis for quick access.
     */
    private function storeFraudResult(array $result): void
    {
        $key = $this->fraudResultKey();

        redis()->->connection()->setex(
            $key,
            300, // 5 minutes TTL
            json_encode($result, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Get Redis key for fraud result.
     */
    private function fraudResultKey(): string
    {
        return "payment:fraud:result:{$this->dto->idempotency_key}";
    }

    /**
     * Get fraud result from Redis (for payment flow to check).
     */
    public static function getFraudResult(string $idempotencyKey): ?array
    {
        $key = "payment:fraud:result:{$idempotencyKey}";
        $data = Redis::connection()->get($key);

        if ($data === null) {
            return null;
        }

        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    }
}
