<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Bonuses\Domain\Entities\BonusTransaction;
use Modules\Bonuses\Domain\Entities\BonusPool;
use Illuminate\Support\Facades\Cache;

final class BonusesFraudDetectionTest extends BaseFraudTest
{
    public function test_bonus_claiming_abuse(): void
    {
        $userId = 1;

        for ($i = 0; $i < 30; $i++) {
            BonusTransaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'bonus_type' => 'welcome_bonus',
                'amount' => 1000,
                'claimed_at' => now(),
            ]);
        }

        $this->fraudControl->checkBonusAbuse([
            'user_id' => $userId,
            'bonus_type' => 'welcome_bonus',
            'claim_count' => 30,
            'max_claims' => 1,
            'total_claimed' => 30000,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::BonusAbuse,
            FraudSeverity::High
        );
    }

    public function test_referral_bonus_fraud(): void
    {
        $referrerId = 1;
        $deviceFingerprint = 'fp_bonus_bot';

        for ($i = 0; $i < 50; $i++) {
            BonusTransaction::create([
                'user_id' => $referrerId,
                'tenant_id' => 1,
                'bonus_type' => 'referral_bonus',
                'amount' => 500,
                'referral_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
            ]);
        }

        $this->fraudControl->checkReferralFraud([
            'referrer_id' => $referrerId,
            'device_fingerprint' => $deviceFingerprint,
            'referral_count' => 50,
            'conversion_rate' => 0,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $referrerId,
            FraudType::ReferralFraud,
            FraudSeverity::High
        );
    }

    public function test_bonus_pool_manipulation(): void
    {
        $poolId = 1;

        $pool = BonusPool::find($poolId);
        $originalAmount = $pool->total_amount;
        $pool->update(['total_amount' => 10000000]); // Artificial inflation

        $this->fraudControl->checkBonusPoolManipulation([
            'pool_id' => $poolId,
            'original_amount' => $originalAmount,
            'new_amount' => 10000000,
            'change_reason' => null,
            'authorized_by' => null,
        ]);

        $this->assertFraudAlertCreated(
            'bonus_pool',
            $poolId,
            FraudType::FinancialFraud,
            FraudSeverity::Critical
        );
    }

    public function test_streak_manipulation(): void
    {
        $userId = 1;

        $this->fraudControl->checkStreakManipulation([
            'user_id' => $userId,
            'current_streak' => 365,
            'avg_activity_per_day' => 1000,
            'humanly_possible' => false,
            'device_fingerprint' => 'fp_streak_bot',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::BonusAbuse,
            FraudSeverity::High
        );
    }

    public function test_bonus_transfer_fraud(): void
    {
        $fromUserId = 1;
        $toUserId = 2;

        for ($i = 0; $i < 25; $i++) {
            BonusTransaction::create([
                'user_id' => $fromUserId,
                'tenant_id' => 1,
                'bonus_type' => 'transfer',
                'amount' => -1000,
                'transfer_to' => $toUserId,
            ]);

            BonusTransaction::create([
                'user_id' => $toUserId,
                'tenant_id' => 1,
                'bonus_type' => 'transfer',
                'amount' => 1000,
                'transfer_from' => $fromUserId,
            ]);
        }

        $this->fraudControl->checkTransferFraud([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'transfer_count' => 25,
            'total_amount' => 25000,
            'time_window_hours' => 2,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $fromUserId,
            FraudType::BonusAbuse,
            FraudSeverity::High
        );
    }
}
