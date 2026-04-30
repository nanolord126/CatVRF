<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Wallet\Domain\Entities\Wallet;
use Modules\Wallet\Domain\Entities\WalletTransaction;
use Modules\Wallet\Domain\Enums\TransactionType;
use Illuminate\Support\Facades\Redis;

final class WalletFraudDetectionTest extends BaseFraudTest
{
    public function test_double_spend_race_condition(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 10000]);

        // Simulate concurrent withdrawal attempts
        $withdrawAmount = 8000;
        $attempts = 5;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => TransactionType::Withdrawal,
                    'amount' => $withdrawAmount,
                    'balance_after' => $wallet->balance - $withdrawAmount,
                ]);

                $wallet->decrement('balance', $withdrawAmount);
            } catch (\Exception $e) {
                // Expected failures due to insufficient balance
            }
        }

        // Check if balance went negative (race condition)
        $wallet->refresh();
        
        if ($wallet->balance < 0) {
            $this->assertFraudAlertCreated(
                'wallet',
                $wallet->id,
                FraudType::RaceCondition,
                FraudSeverity::Critical
            );
        }
    }

    public function test_rapid_deposit_withdrawal_pattern(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 0]);

        // Simulate money laundering pattern: deposit -> immediate withdrawal
        for ($i = 0; $i < 20; $i++) {
            // Deposit
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Deposit,
                'amount' => 10000,
                'balance_after' => $wallet->balance + 10000,
            ]);
            $wallet->increment('balance', 10000);

            // Immediate withdrawal
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Withdrawal,
                'amount' => 10000,
                'balance_after' => $wallet->balance - 10000,
            ]);
            $wallet->decrement('balance', 10000);
        }

        $this->fraudControl->checkMoneyLaundering([
            'wallet_id' => $wallet->id,
            'deposit_count' => 20,
            'withdrawal_count' => 20,
            'time_window_minutes' => 30,
        ]);

        $this->assertFraudAlertCreated(
            'wallet',
            $wallet->id,
            FraudType::MoneyLaundering,
            FraudSeverity::Critical
        );
    }

    public function test_bonus_abuse(): void
    {
        $userId = 1;

        // Simulate user creating multiple accounts to claim bonuses
        for ($i = 0; $i < 10; $i++) {
            $wallet = Wallet::factory()->create([
                'user_id' => $userId + $i,
                'tenant_id' => 1,
                'ip_address' => '192.168.1.50', // Same IP
                'device_fingerprint' => 'fp_bonus_abuse', // Same device
            ]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => TransactionType::Bonus,
                'amount' => 1000,
                'balance_after' => 1000,
                'metadata' => ['bonus_type' => 'welcome_bonus'],
            ]);
        }

        $this->fraudControl->checkBonusAbuse([
            'ip_address' => '192.168.1.50',
            'device_fingerprint' => 'fp_bonus_abuse',
            'account_count' => 10,
            'bonus_claimed' => 10000,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::BonusAbuse,
            FraudSeverity::High
        );
    }

    public function test_unusual_transaction_pattern(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 100000]);

        // Normal pattern: small regular transactions
        for ($i = 0; $i < 30; $i++) {
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $i % 2 === 0 ? TransactionType::Deposit : TransactionType::Withdrawal,
                'amount' => rand(500, 2000),
                'balance_after' => $wallet->balance,
            ]);
        }

        // Sudden large transfer
        WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Transfer,
            'amount' => 95000,
            'balance_after' => 5000,
        ]);

        $patternScore = $this->fraudML->detectUnusualPattern($wallet->id);

        $this->assertGreaterThan(80, $patternScore, 'Unusual pattern should be detected');

        if ($patternScore > 80) {
            $this->assertFraudAlertCreated(
                'wallet',
                $wallet->id,
                FraudType::UnusualPattern,
                FraudSeverity::High
            );
        }
    }

    public function test_wallet_balance_manipulation(): void
    {
        $wallet = Wallet::factory()->create(['balance' => 5000]);

        // Simulate direct balance manipulation
        $originalBalance = $wallet->balance;
        $wallet->update(['balance' => 500000]); // Artificial inflation

        $this->fraudControl->checkBalanceManipulation($wallet, [
            'original_balance' => $originalBalance,
            'new_balance' => 500000,
            'change_reason' => null, // No valid reason
        ]);

        $this->assertFraudAlertCreated(
            'wallet',
            $wallet->id,
            FraudType::DataTampering,
            FraudSeverity::Critical
        );
    }
}
