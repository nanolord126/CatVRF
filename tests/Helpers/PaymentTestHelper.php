<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Payment Test Helper
 *
 * Provides reusable test data and utilities for Payment vertical testing.
 * Focuses on idempotency, double-charge prevention, and webhook processing.
 */
class PaymentTestHelper
{
    /**
     * Create a test payment
     */
    public static function createPayment(array $overrides = []): Payment
    {
        return Payment::factory()->create(array_merge([
            'amount' => 10000, // 100.00 in cents
            'currency' => 'RUB',
            'status' => 'pending',
            'payment_method' => 'card',
            'idempotency_key' => self::generateIdempotencyKey(),
        ], $overrides));
    }

    /**
     * Create a completed transaction
     */
    public static function createTransaction(array $overrides = []): Transaction
    {
        return Transaction::factory()->create(array_merge([
            'amount' => 10000,
            'status' => 'completed',
            'type' => 'payment',
        ], $overrides));
    }

    /**
     * Generate unique idempotency key
     */
    public static function generateIdempotencyKey(): string
    {
        return 'test_'.uniqid().'_'.time();
    }

    /**
     * Assert that payment is idempotent
     * Same idempotency key should not create duplicate payments
     */
    public static function assertIdempotent(callable $paymentAction, string $idempotencyKey): void
    {
        $initialCount = Payment::where('idempotency_key', $idempotencyKey)->count();

        // Execute same payment action twice
        $paymentAction();
        $paymentAction();

        $finalCount = Payment::where('idempotency_key', $idempotencyKey)->count();

        expect($finalCount)->toBe($initialCount + 1);
    }

    /**
     * Assert that double-charge is prevented
     */
    public static function assertNoDoubleCharge(string $paymentId): void
    {
        $transactions = Transaction::where('payment_id', $paymentId)->get();

        $completedTransactions = $transactions->filter(fn ($t) => $t->status === 'completed');

        expect($completedTransactions->count())->toBe(1);
    }

    /**
     * Simulate webhook from payment gateway
     */
    public static function simulateWebhook(string $paymentId, string $status): array
    {
        return [
            'event' => 'payment.'.$status,
            'data' => [
                'id' => $paymentId,
                'status' => $status,
                'amount' => 10000,
                'currency' => 'RUB',
                'timestamp' => now()->toIso8601String(),
            ],
            'signature' => hash_hmac('sha256', json_encode(['id' => $paymentId]), config('payment.gateway_secret')),
        ];
    }

    /**
     * Assert webhook signature is valid
     */
    public static function assertWebhookSignatureValid(array $webhookPayload): void
    {
        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($webhookPayload['data']),
            config('payment.gateway_secret')
        );

        expect($webhookPayload['signature'])->toBe($expectedSignature);
    }

    /**
     * Create refund scenario
     */
    public static function createRefundScenario(Payment $payment): array
    {
        return [
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'reason' => 'customer_request',
            'idempotency_key' => self::generateIdempotencyKey(),
        ];
    }

    /**
     * Assert refund is idempotent
     */
    public static function assertRefundIdempotent(callable $refundAction, string $idempotencyKey): void
    {
        $initialCount = Transaction::where('type', 'refund')
            ->where('idempotency_key', $idempotencyKey)
            ->count();

        $refundAction();
        $refundAction();

        $finalCount = Transaction::where('type', 'refund')
            ->where('idempotency_key', $idempotencyKey)
            ->count();

        expect($finalCount)->toBe($initialCount + 1);
    }

    /**
     * Lock payment for processing (simulates distributed lock)
     */
    public static function acquirePaymentLock(string $paymentId, int $ttl = 30): bool
    {
        $lockKey = "payment:lock:{$paymentId}";

        return Redis::set($lockKey, '1', 'EX', $ttl, 'NX');
    }

    /**
     * Release payment lock
     */
    public static function releasePaymentLock(string $paymentId): void
    {
        $lockKey = "payment:lock:{$paymentId}";
        Redis::del($lockKey);
    }

    /**
     * Assert payment lock is acquired
     */
    public static function assertPaymentLockAcquired(string $paymentId): void
    {
        $lockKey = "payment:lock:{$paymentId}";
        expect(Redis::exists($lockKey))->toBe(1);
    }

    /**
     * Create concurrent payment scenario
     */
    public static function createConcurrentPaymentScenario(int $count = 5): array
    {
        $payments = [];

        for ($i = 0; $i < $count; $i++) {
            $payments[] = self::createPayment([
                'idempotency_key' => self::generateIdempotencyKey(),
            ]);
        }

        return $payments;
    }

    /**
     * Assert transaction atomicity
     */
    public static function assertTransactionAtomicity(callable $transaction): void
    {
        $initialPaymentCount = Payment::count();
        $initialTransactionCount = Transaction::count();

        try {
            DB::transaction($transaction);
        } catch (\Exception $e) {
            // Transaction should rollback
        }

        expect(Payment::count())->toBe($initialPaymentCount);
        expect(Transaction::count())->toBe($initialTransactionCount);
    }

    /**
     * Mock payment gateway response
     */
    public static function mockGatewayResponse(string $status, array $data = []): array
    {
        return array_merge([
            'success' => $status === 'success',
            'status' => $status,
            'transaction_id' => 'txn_'.uniqid(),
            'amount' => 10000,
            'currency' => 'RUB',
        ], $data);
    }
}
