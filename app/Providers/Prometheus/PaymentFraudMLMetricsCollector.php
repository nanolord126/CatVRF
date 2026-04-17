<?php

declare(strict_types=1);

namespace App\Providers\Prometheus;

use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Prometheus\Histogram;
use Prometheus\Gauge;
use Prometheus\Counter;

/**
 * PaymentFraudMLMetricsCollector - Dedicated Prometheus metrics for payment fraud
 * 
 * CRITICAL METRICS:
 * 1. fraud_ml_payment_score - Distribution of fraud scores
 * 2. fraud_ml_payment_latency - Inference latency (ms)
 * 3. fraud_ml_payment_block_rate - Block rate by vertical
 * 4. fraud_ml_payment_false_positive_rate_medical - Medical false-positive rate
 * 5. fraud_ml_payment_emergency_rate - Emergency payment processing rate
 * 6. fraud_ml_payment_cache_hit_rate - Idempotency cache hit rate
 * 
 * CANON 2026 - Production Ready
 */
final readonly class PaymentFraudMLMetricsCollector
{
    private Histogram $scoreHistogram;
    private Histogram $latencyHistogram;
    private Gauge $blockRateGauge;
    private Gauge $falsePositiveRateGauge;
    private Counter $emergencyCounter;
    private Counter $cacheHitCounter;
    private Counter $cacheMissCounter;

    public function __construct(
        private CollectorRegistry $registry
    ) {
        $this->scoreHistogram = $registry->getOrRegisterHistogram(
            'fraud_ml',
            'payment_score',
            'Payment fraud score distribution',
            ['vertical_code', 'urgency_level'],
            [0.0, 0.1, 0.25, 0.5, 0.75, 0.85, 0.9, 0.95, 1.0]
        );

        $this->latencyHistogram = $registry->getOrRegisterHistogram(
            'fraud_ml',
            'payment_latency_ms',
            'Payment fraud inference latency in milliseconds',
            ['vertical_code', 'cached'],
            [1, 5, 10, 25, 50, 100, 250, 500, 1000]
        );

        $this->blockRateGauge = $registry->getOrRegisterGauge(
            'fraud_ml',
            'payment_block_rate',
            'Payment fraud block rate by vertical',
            ['vertical_code']
        );

        $this->falsePositiveRateGauge = $registry->getOrRegisterGauge(
            'fraud_ml',
            'payment_false_positive_rate_medical',
            'Medical payment false-positive rate (calculated from user appeals)',
            []
        );

        $this->emergencyCounter = $registry->getOrRegisterCounter(
            'fraud_ml',
            'payment_emergency_total',
            'Total emergency payments processed',
            ['vertical_code', 'decision']
        );

        $this->cacheHitCounter = $registry->getOrRegisterCounter(
            'fraud_ml',
            'payment_cache_hit_total',
            'Payment fraud cache hits (idempotency)',
            ['vertical_code']
        );

        $this->cacheMissCounter = $registry->getOrRegisterCounter(
            'fraud_ml',
            'payment_cache_miss_total',
            'Payment fraud cache misses (idempotency)',
            ['vertical_code']
        );
    }

    /**
     * Record fraud score
     */
    public function recordScore(float $score, ?string $verticalCode = null, ?string $urgencyLevel = null): void
    {
        $this->scoreHistogram->observe(
            $score,
            [
                'vertical_code' => $verticalCode ?? 'payment',
                'urgency_level' => $urgencyLevel ?? 'none',
            ]
        );
    }

    /**
     * Record inference latency
     */
    public function recordLatency(float $latencyMs, ?string $verticalCode = null, bool $cached = false): void
    {
        $this->latencyHistogram->observe(
            $latencyMs,
            [
                'vertical_code' => $verticalCode ?? 'payment',
                'cached' => $cached ? 'true' : 'false',
            ]
        );
    }

    /**
     * Update block rate for a vertical
     */
    public function setBlockRate(float $rate, ?string $verticalCode = null): void
    {
        $this->blockRateGauge->set(
            $rate,
            ['vertical_code' => $verticalCode ?? 'payment']
        );
    }

    /**
     * Update Medical false-positive rate
     */
    public function setFalsePositiveRate(float $rate): void
    {
        $this->falsePositiveRateGauge->set($rate);
    }

    /**
     * Record emergency payment
     */
    public function recordEmergency(?string $verticalCode = null, string $decision = 'allow'): void
    {
        $this->emergencyCounter->inc(
            [
                'vertical_code' => $verticalCode ?? 'payment',
                'decision' => $decision,
            ]
        );
    }

    /**
     * Record cache hit
     */
    public function recordCacheHit(?string $verticalCode = null): void
    {
        $this->cacheHitCounter->inc(
            ['vertical_code' => $verticalCode ?? 'payment']
        );
    }

    /**
     * Record cache miss
     */
    public function recordCacheMiss(?string $verticalCode = null): void
    {
        $this->cacheMissCounter->inc(
            ['vertical_code' => $verticalCode ?? 'payment']
        );
    }

    /**
     * Calculate and update block rate from recent data
     */
    public function calculateBlockRate(string $verticalCode, int $total, int $blocked): void
    {
        if ($total === 0) {
            return;
        }

        $rate = $blocked / $total;
        $this->setBlockRate($rate, $verticalCode);
    }

    /**
     * Get cache hit rate
     */
    public function getCacheHitRate(?string $verticalCode = null): float
    {
        $hits = $this->cacheHitCounter->get()['fraud_ml_payment_cache_hit_total'] ?? 0;
        $misses = $this->cacheMissCounter->get()['fraud_ml_payment_cache_miss_total'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return $hits / $total;
    }

    /**
     * Record complete fraud check result
     */
    public function recordFraudCheck(array $result, float $latencyMs, bool $cached): void
    {
        $verticalCode = $result['vertical_code'] ?? 'payment';
        $urgencyLevel = $result['urgency_level'] ?? 'none';
        $decision = $result['decision'] ?? 'allow';
        $score = $result['score'] ?? 0.0;

        $this->recordScore($score, $verticalCode, $urgencyLevel);
        $this->recordLatency($latencyMs, $verticalCode, $cached);

        if ($cached) {
            $this->recordCacheHit($verticalCode);
        } else {
            $this->recordCacheMiss($verticalCode);
        }

        // Track emergency payments
        if ($result['is_emergency'] ?? false) {
            $this->recordEmergency($verticalCode, $decision);
        }
    }
}
