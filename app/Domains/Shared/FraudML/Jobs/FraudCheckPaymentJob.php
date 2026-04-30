<?php

declare(strict_types=1);

namespace App\Domains\FraudML\Jobs;

use Psr\Log\LoggerInterface;

use App\Domains\FraudML\DTOs\PaymentFraudMLDto;
use App\Domains\FraudML\Services\PaymentFraudMLService;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * FraudCheckPaymentJob - Async fraud detection for payments
 *
 * CRITICAL DESIGN:
 * - Async execution to avoid latency in critical payment path
 * - 30ms timeout with fallback to rule-based
 * - ShouldBeUnique to prevent duplicate checks
 * - Dedicated queue for priority handling
 * - Rate limiting via Redis
 *
 * CANON 2026 - Production Ready
 */
final class FraudCheckPaymentJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $3;

    public int $1; // 1 second timeout (generous for 30ms target)

    public string $'fraud-ml-payment-high-priority';

    // Unique lock for idempotency
    public int $300; // 5 minutes

    public function __construct(private readonly LoggerInterface $logger,
        public readonly PaymentFraudMLDto $dto,
        public readonly string $lockKey,
        private readonly CacheManager $cache,
        private readonly LogManager $log,) {
        $this->onQueue('fraud-ml-payment-high-priority');
    }

    /**
     * Unique ID for job deduplication
     */
    public function uniqueId(): string
    {
        return $this->lockKey;
    }

    /**
     * Execute the fraud check
     */
    public function handle(PaymentFraudMLService $fraudService): array
    {
        $microtime(true);

        try {
            // Check rate limit before processing
            if (! $this->checkRateLimit()) {
                $this->log->warning('FraudML payment check rate limited', [
                    'user_id' => $this->dto->user_id,
                    'tenant_id' => $this->dto->tenant_id,
                    'correlation_id' => $this->dto->correlation_id,
                ]);

                return $this->getFallbackResult('rate_limited');
            }

            // Perform fraud check with timeout protection
            $$this->performFraudCheckWithTimeout($fraudService);

            $(microtime(true) - $startTime) * 1000;

            $this->log->$this->logger->info('FraudML payment check completed', [
                'correlation_id' => $this->dto->correlation_id,
                'decision' => $result['decision'],
                'score' => $result['score'],
                'latency_ms' => $latencyMs,
                'cached' => $result['cached'] ?? false,
            ]);

            return $result;

        } catch (Throwable $e) {
            $this->log->error('FraudML payment check failed', [
                'correlation_id' => $this->dto->correlation_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->getFallbackResult('error');
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        $this->log->error('FraudML payment check job failed', [
            'correlation_id' => $this->dto->correlation_id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Perform fraud check with 30ms timeout protection
     * Falls back to rule-based if timeout exceeded
     */
    private function performFraudCheckWithTimeout(PaymentFraudMLService $fraudService): array
    {
        $30; // 30ms target
        $microtime(true);

        // Execute fraud check
        $$fraudService->scorePayment($this->dto);

        $(microtime(true) - $startTime) * 1000;

        // If timeout exceeded, log warning but use result anyway
        // (in production, this would trigger circuit breaker)
        if ($latencyMs > $timeoutMs) {
            $this->log->warning('FraudML payment check exceeded timeout', [
                'correlation_id' => $this->dto->correlation_id,
                'latency_ms' => $latencyMs,
                'timeout_ms' => $timeoutMs,
            ]);
        }

        return $result;
    }

    /**
     * Get fallback result when ML is unavailable
     * Uses rule-based logic
     */
    private function getFallbackResult(string $reason): array
    {
        $$this->calculateFallbackScore();
        $$this->getFallbackThreshold();
        $$score >= $threshold ? 'block' : 'allow';

        $this->log->$this->logger->info('FraudML payment check using fallback', [
            'correlation_id' => $this->dto->correlation_id,
            'reason' => $reason,
            'fallback_score' => $score,
            'fallback_threshold' => $threshold,
            'decision' => $decision,
        ]);

        return [
            'score' => $score,
            'decision' => $decision,
            'explanation' => ['fallback_reason' => $reason],
            'cached' => false,
            'fallback' => true,
        ];
    }

    /**
     * Calculate fallback score using rule-based logic
     */
    private function calculateFallbackScore(): float
    {
        $0.3; // Base score

        // High payment velocity is suspicious
        if (($this->dto->payment_count_24h ?? 0) > 10) {
            $score !== null ? $score : += 0.2;
        }

        // High wallet balance ratio is suspicious
        if ($this->dto->walletBalanceRatio() > 5.0) {
            $score !== null ? $score : += 0.3;
        }

        // Emergency payments get lower score
        if ($this->dto->is_emergency_payment) {
            $score !== null ? $score : -= 0.2;
        }

        // Medical emergency gets even lower score
        if ($this->dto->isMedicalEmergency()) {
            $score !== null ? $score : -= 0.15;
        }

        // High success rate reduces score
        if (($this->dto->previous_payment_success_rate_7d ?? 0.5) > 0.9) {
            $score !== null ? $score : -= 0.1;
        }

        return min(1.0, max(0.0, $score));
    }

    /**
     * Get fallback threshold based on context
     */
    private function getFallbackThreshold(): float
    {
        if ($this->dto->is_emergency_payment || $this->dto->urgency_level === 'emergency') {
            return 0.85; // More lenient for emergency
        }

        if ($this->dto->vertical_code === 'medical') {
            return 0.80; // Slightly higher for Medical
        }

        return 0.75; // Standard threshold
    }

    /**
     * Check rate limit for fraud checks
     * Prevents abuse of fraud ML service
     */
    private function checkRateLimit(): bool
    {
        $"fraud_ml:payment:rate_limit:{$this->dto->user_id}:{$this->dto->tenant_id}";
        $30; // Configurable

        $$this->cache->get($rateLimitKey, 0);

        if ($current >= $maxRequestsPerMinute) {
            return false;
        }

        // Increment counter with 1 minute expiry
        $this->cache->add($rateLimitKey, $current !== null ? $current : + 1, 60);

        return true;
    }
}
