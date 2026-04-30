<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Payment\Domain\Entities\Transaction;
use Modules\Payment\Domain\Enums\TransactionStatus;
use Modules\Payment\Domain\Enums\PaymentMethod;
use Illuminate\Support\Facades\Cache;

final class PaymentFraudDetectionTest extends BaseFraudTest
{
    public function test_replay_attack_same_idempotency_key(): void
    {
        $idempotencyKey = 'key_test_12345';
        $transactionData = $this->createTestTransaction([
            'idempotency_key' => $idempotencyKey,
        ]);

        // First transaction
        $tx1 = Transaction::create($transactionData);
        $tx1->update(['status' => TransactionStatus::Completed]);

        // Attempt replay with same idempotency key
        $this->fraudControl->checkIdempotencyBreach([
            'idempotency_key' => $idempotencyKey,
            'original_amount' => $transactionData['amount'],
            'new_amount' => $transactionData['amount'] * 2, // Different amount
            'attempt_time' => now(),
        ]);

        $this->assertFraudAlertCreated(
            'transaction',
            $tx1->id,
            FraudType::ReplayAttack,
            FraudSeverity::Critical
        );
    }

    public function test_card_testing_pattern(): void
    {
        $ipAddress = '192.168.1.200';
        $userId = 1;

        // Simulate card testing - multiple small transactions with different cards
        for ($i = 0; $i < 20; $i++) {
            Transaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => 100, // Small amount for testing
                'currency' => 'RUB',
                'payment_method' => PaymentMethod::Card,
                'card_last_four' => str_pad((string)$i, 4, '0'),
                'ip_address' => $ipAddress,
                'status' => TransactionStatus::Failed,
            ]);
        }

        $this->fraudControl->checkCardTesting([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'attempt_count' => 20,
            'time_window_minutes' => 5,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CardTesting,
            FraudSeverity::Critical
        );
    }

    public function test_high_velocity_transactions(): void
    {
        $userId = 1;

        // Simulate high-velocity transactions
        for ($i = 0; $i < 50; $i++) {
            Transaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => 5000,
                'currency' => 'RUB',
                'payment_method' => PaymentMethod::Card,
                'status' => TransactionStatus::Completed,
            ]);
        }

        $velocityScore = $this->fraudML->calculateVelocityScore($userId, 60); // 1 minute window

        $this->assertGreaterThan(90, $velocityScore, 'High velocity should trigger fraud');

        if ($velocityScore > 90) {
            $this->assertFraudAlertCreated(
                'user',
                $userId,
                FraudType::HighVelocity,
                FraudSeverity::Critical
            );
        }
    }

    public function test_refund_fraud(): void
    {
        $userId = 1;

        // Create transactions and then refund them
        for ($i = 0; $i < 15; $i++) {
            $tx = Transaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => 10000,
                'currency' => 'RUB',
                'payment_method' => PaymentMethod::Card,
                'status' => TransactionStatus::Completed,
            ]);

            // Immediate refund
            $tx->update([
                'status' => TransactionStatus::Refunded,
                'refunded_at' => now(),
            ]);
        }

        $refundRate = $this->fraudML->calculateRefundRate($userId);
        
        $this->assertGreaterThan(80, $refundRate, 'High refund rate should trigger fraud');

        if ($refundRate > 80) {
            $this->assertFraudAlertCreated(
                'user',
                $userId,
                FraudType::RefundFraud,
                FraudSeverity::High
            );
        }
    }

    public function test_amount_anomaly_detection(): void
    {
        $userId = 1;

        // Normal transactions
        for ($i = 0; $i < 10; $i++) {
            Transaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'amount' => rand(1000, 5000),
                'currency' => 'RUB',
                'payment_method' => PaymentMethod::Card,
                'status' => TransactionStatus::Completed,
            ]);
        }

        // Anomalous large transaction
        $largeTx = Transaction::create([
            'user_id' => $userId,
            'tenant_id' => 1,
            'amount' => 1000000, // Much larger than usual
            'currency' => 'RUB',
            'payment_method' => PaymentMethod::Card,
            'status' => TransactionStatus::Pending,
        ]);

        $anomalyScore = $this->fraudML->detectAmountAnomaly($userId, $largeTx->amount);

        $this->assertGreaterThan(85, $anomalyScore, 'Amount anomaly should be detected');

        if ($anomalyScore > 85) {
            $this->assertFraudAlertCreated(
                'transaction',
                $largeTx->id,
                FraudType::AmountAnomaly,
                FraudSeverity::High
            );
        }
    }
}
