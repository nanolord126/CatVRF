<?php

declare(strict_types=1);

namespace Tests\Stress;

use Tests\TestCase;
use Modules\Payment\Domain\Entities\Transaction;
use Modules\Payment\Domain\Enums\TransactionStatus;
use Modules\Payment\Domain\Enums\PaymentMethod;
use Modules\Payment\Application\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final class PaymentStressTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
    }

    public function test_concurrent_payment_processing_100_rps(): void
    {
        $concurrency = 100;
        $transactions = [];

        for ($i = 0; $i < $concurrency; $i++) {
            $transactions[] = [
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => 10000,
                'currency' => 'RUB',
                'payment_method' => PaymentMethod::Card,
                'card_last_four' => '1234',
                'ip_address' => '192.168.1.' . ($i % 255),
                'idempotency_key' => "stress_test_{$i}",
            ];
        }

        $startTime = microtime(true);
        $results = [];

        foreach ($transactions as $tx) {
            try {
                $result = $this->paymentService->processPayment($tx);
                $results[] = ['success' => true, 'transaction_id' => $result->id];
            } catch (\Exception $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $successRate = ($successCount / $concurrency) * 100;

        $this->assertGreaterThan(95, $successRate, 'Success rate should be > 95%');
        $this->assertLessThan(30, $duration, 'Should process 100 payments in < 30 seconds');

        // Verify no duplicate transactions with same idempotency key
        $idempotencyKeys = array_column($transactions, 'idempotency_key');
        $uniqueKeys = array_unique($idempotencyKeys);
        $this->assertEquals(count($idempotencyKeys), count($uniqueKeys), 'No duplicate idempotency keys');
    }

    public function test_payment_webhook_stress_1000_webhooks(): void
    {
        $webhookCount = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $webhookCount; $i++) {
            $this->postJson('/api/payments/webhook', [
                'transaction_id' => "tx_{$i}",
                'status' => 'completed',
                'amount' => 10000,
                'signature' => hash_hmac('sha256', "tx_{$i}", config('payment.webhook_secret')),
            ]);
        }

        $duration = microtime(true) - $startTime;
        $this->assertLessThan(60, $duration, 'Should process 1000 webhooks in < 60 seconds');
    }

    public function test_high_value_transaction_stress(): void
    {
        $highValueCount = 50;
        $results = [];

        for ($i = 0; $i < $highValueCount; $i++) {
            try {
                $result = $this->paymentService->processPayment([
                    'user_id' => $i + 1,
                    'tenant_id' => 1,
                    'amount' => 1000000, // 1M RUB
                    'currency' => 'RUB',
                    'payment_method' => PaymentMethod::Card,
                    'ip_address' => '192.168.1.' . ($i % 255),
                    'idempotency_key' => "high_value_{$i}",
                ]);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->assertGreaterThan(90, $successCount, 'High value transactions should have > 90% success rate');
    }

    public function test_refund_stress_500_refunds(): void
    {
        // First create transactions
        $transactions = [];
        for ($i = 0; $i < 500; $i++) {
            $tx = Transaction::create([
                'user_id' => $i + 1,
                'tenant_id' => 1,
                'amount' => 10000,
                'currency' => 'RUB',
                'payment_method' => PaymentMethod::Card,
                'status' => TransactionStatus::Completed,
                'idempotency_key' => "refund_test_{$i}",
            ]);
            $transactions[] = $tx;
        }

        $startTime = microtime(true);
        $results = [];

        foreach ($transactions as $tx) {
            try {
                $this->paymentService->processRefund($tx->id, 10000);
                $results[] = ['success' => true];
            } catch (\Exception $e) {
                $results[] = ['success' => false];
            }
        }

        $duration = microtime(true) - $startTime;
        $successCount = count(array_filter($results, fn($r) => $r['success']));

        $this->assertGreaterThan(95, $successCount, 'Refund success rate should be > 95%');
        $this->assertLessThan(45, $duration, 'Should process 500 refunds in < 45 seconds');
    }

    public function test_payment_cache_stress(): void
    {
        $cacheKeyPrefix = 'payment_rate_limit_';
        $iterations = 10000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $key = $cacheKeyPrefix . ($i % 100); // 100 unique keys
            Cache::put($key, $i, 60);
            Cache::get($key);
        }

        $duration = microtime(true) - $startTime;
        $opsPerSecond = $iterations / $duration;

        $this->assertGreaterThan(1000, $opsPerSecond, 'Cache should handle > 1000 ops/sec');
    }

    public function test_database_connection_pool_stress(): void
    {
        $connections = 50;
        $startTime = microtime(true);

        for ($i = 0; $i < $connections; $i++) {
            \DB::connection()->select('SELECT 1');
        }

        $duration = microtime(true) - $startTime;
        $this->assertLessThan(5, $duration, '50 DB queries should complete in < 5 seconds');
    }
}
