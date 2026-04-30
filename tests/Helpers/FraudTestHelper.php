<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Domains\FraudML\Services\FraudMLService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

/**
 * Fraud Test Helper
 *
 * Provides reusable test data and utilities for Fraud detection testing.
 * Focuses on fraud scenarios, ML model predictions, and rate limiting.
 */
class FraudTestHelper
{
    /**
     * Create a legitimate transaction data
     */
    public static function createLegitimateTransaction(array $overrides = []): array
    {
        return array_merge([
            'user_id' => 1,
            'amount' => 5000,
            'currency' => 'RUB',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'device_fingerprint' => 'fp_'.md5('legitimate_device'),
            'transaction_count_24h' => 3,
            'account_age_days' => 365,
        ], $overrides);
    }

    /**
     * Create a suspicious transaction data
     */
    public static function createSuspiciousTransaction(array $overrides = []): array
    {
        return array_merge([
            'user_id' => 999,
            'amount' => 500000, // Large amount
            'currency' => 'RUB',
            'ip_address' => '10.0.0.1', // Suspicious IP
            'user_agent' => 'Bot/1.0',
            'device_fingerprint' => 'fp_'.md5('new_device_'.time()),
            'transaction_count_24h' => 50, // High frequency
            'account_age_days' => 1, // New account
        ], $overrides);
    }

    /**
     * Create a fraud scenario
     */
    public static function createFraudScenario(string $type = 'velocity'): array
    {
        return match($type) {
            'velocity' => [
                'user_id' => 1,
                'transactions_count_1min' => 10,
                'transactions_count_5min' => 25,
                'total_amount_5min' => 100000,
            ],
            'amount' => [
                'user_id' => 1,
                'amount' => 1000000, // 1M RUB
                'avg_amount_30d' => 5000,
            ],
            'location' => [
                'user_id' => 1,
                'ip_country' => 'CN',
                'billing_country' => 'US',
                'shipping_country' => 'RU',
            ],
            'device' => [
                'user_id' => 1,
                'device_fingerprint' => 'fp_new_'.uniqid(),
                'known_devices' => ['fp_old_1', 'fp_old_2'],
            ],
            default => [],
        };
    }

    /**
     * Mock FraudMLService prediction
     */
    public static function mockFraudMLPrediction(float $riskScore = 0.1): void
    {
        $mock = \Mockery::mock(FraudMLService::class);
        $mock->shouldReceive('predict')
            ->andReturn([
                'risk_score' => $riskScore,
                'is_fraud' => $riskScore > 0.5,
                'confidence' => 0.95,
                'features_used' => ['amount', 'velocity', 'device'],
            ]);

        app()->instance(FraudMLService::class, $mock);
    }

    /**
     * Assert that fraud check was performed
     */
    public static function assertFraudCheckPerformed(array $auditLogs): void
    {
        $fraudChecks = array_filter(
            $auditLogs,
            fn ($log) => isset($log['action']) && str_contains($log['action'], 'fraud')
        );

        expect($fraudChecks)->not->toBeEmpty();
    }

    /**
     * Assert that transaction was blocked due to fraud
     */
    public static function assertTransactionBlocked(array $fraudResult): void
    {
        expect($fraudResult['is_fraud'])->toBeTrue();
        expect($fraudResult['action'])->toBe('block');
    }

    /**
     * Assert that transaction was allowed
     */
    public static function assertTransactionAllowed(array $fraudResult): void
    {
        expect($fraudResult['is_fraud'])->toBeFalse();
        expect($fraudResult['action'])->toBe('allow');
    }

    /**
     * Set rate limit for user
     */
    public static function setRateLimit(int $userId, int $limit, int $window = 60): void
    {
        $key = "rate_limit:user:{$userId}";
        Redis::set($key, $limit, 'EX', $window);
    }

    /**
     * Get current rate limit count
     */
    public static function getRateLimitCount(int $userId): int
    {
        $key = "rate_limit:user:{$userId}";

        return (int) Redis::get($key) ?? 0;
    }

    /**
     * Increment rate limit
     */
    public static function incrementRateLimit(int $userId): int
    {
        $key = "rate_limit:user:{$userId}";

        return Redis::incr($key);
    }

    /**
     * Assert rate limit exceeded
     */
    public static function assertRateLimitExceeded(int $userId): void
    {
        $count = self::getRateLimitCount($userId);
        $limit = (int) Cache::get("rate_limit_config:user:{$userId}", 10);

        expect($count)->toBeGreaterThan($limit);
    }

    /**
     * Create fraud audit log entry
     */
    public static function createFraudAuditLog(array $data): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $data['user_id'] ?? null,
            'action' => $data['action'] ?? 'fraud_check',
            'risk_score' => $data['risk_score'] ?? 0.0,
            'result' => $data['result'] ?? 'allow',
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ];
    }

    /**
     * Assert fraud detection metrics are recorded
     */
    public static function assertFraudMetricsRecorded(array $metrics): void
    {
        expect($metrics)->toHaveKeys([
            'fraud_checks_total',
            'fraud_detected_total',
            'false_positives_total',
            'false_negatives_total',
        ]);
    }

    /**
     * Simulate ML model drift
     */
    public static function simulateModelDrift(float $driftAmount = 0.3): void
    {
        // This would typically be done by changing model predictions
        // For testing, we mock the drift detection
        Cache::put('ml_model_drift_detected', true, now()->addHour());
        Cache::put('ml_model_drift_amount', $driftAmount, now()->addHour());
    }

    /**
     * Assert model drift was detected
     */
    public static function assertModelDriftDetected(): void
    {
        expect(Cache::get('ml_model_drift_detected'))->toBeTrue();
        expect(Cache::get('ml_model_drift_amount'))->toBeGreaterThan(0.2);
    }

    /**
     * Create concurrent fraud check scenario
     */
    public static function createConcurrentFraudChecks(int $count = 10): array
    {
        $scenarios = [];

        for ($i = 0; $i < $count; $i++) {
            $scenarios[] = self::createSuspiciousTransaction([
                'user_id' => 1000 + $i,
                'amount' => 100000 + ($i * 10000),
            ]);
        }

        return $scenarios;
    }

    /**
     * Assert fraud check is idempotent
     */
    public static function assertFraudCheckIdempotent(callable $checkAction, string $transactionId): void
    {
        $firstResult = $checkAction();
        $secondResult = $checkAction();

        expect($firstResult)->toEqual($secondResult);
    }
}
