<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Loyalty\Domain\Entities\PointsTransaction;
use Modules\Loyalty\Domain\Entities\Reward;
use Modules\Loyalty\Domain\Enums\TransactionType;
use Illuminate\Support\Facades\Cache;

final class LoyaltyFraudDetectionTest extends BaseFraudTest
{
    public function test_points_accumulation_abuse(): void
    {
        $userId = 1;

        // Simulate rapid points accumulation through fake purchases
        for ($i = 0; $i < 50; $i++) {
            PointsTransaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'type' => TransactionType::Earned,
                'points' => 1000,
                'source' => 'purchase',
                'order_id' => "FAKE_ORDER_{$i}",
            ]);
        }

        $this->fraudControl->checkLoyaltyFraud([
            'user_id' => $userId,
            'points_earned' => 50000,
            'time_window_minutes' => 60,
            'transaction_count' => 50,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LoyaltyFraud,
            FraudSeverity::High
        );
    }

    public function test_reward_redemption_abuse(): void
    {
        $userId = 1;
        $reward = Reward::factory()->create(['points_required' => 5000]);

        // User redeeming same reward multiple times
        for ($i = 0; $i < 15; $i++) {
            PointsTransaction::create([
                'user_id' => $userId,
                'tenant_id' => 1,
                'type' => TransactionType::Redeemed,
                'points' => -5000,
                'reward_id' => $reward->id,
            ]);
        }

        $this->fraudControl->checkRewardAbuse([
            'user_id' => $userId,
            'reward_id' => $reward->id,
            'redemption_count' => 15,
            'time_window_days' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LoyaltyFraud,
            FraudSeverity::High
        );
    }

    public function test_referral_fraud(): void
    {
        $referrerId = 1;

        // Simulate fake referrals
        for ($i = 0; $i < 30; $i++) {
            $this->fraudControl->checkReferralFraud([
                'referrer_id' => $referrerId,
                'referred_user_id' => $i + 1000,
                'ip_address' => '192.168.1.70',
                'device_fingerprint' => 'fp_referral_bot',
                'conversion_rate' => 0, // No actual conversions
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $referrerId,
            FraudType::ReferralFraud,
            FraudSeverity::High
        );
    }

    public function test_tier_manipulation(): void
    {
        $userId = 1;

        // Simulate artificial tier progression
        $this->fraudControl->checkTierManipulation([
            'user_id' => $userId,
            'current_tier' => 'bronze',
            'target_tier' => 'platinum',
            'points_before' => 1000,
            'points_after' => 100000,
            'time_to_achieve_hours' => 2,
            'legitimate_spend' => 0,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::LoyaltyFraud,
            FraudSeverity::Medium
        );
    }

    public function test_points_transfer_fraud(): void
    {
        $fromUserId = 1;
        $toUserId = 2;

        // Simulate suspicious points transfers
        for ($i = 0; $i < 20; $i++) {
            PointsTransaction::create([
                'user_id' => $fromUserId,
                'tenant_id' => 1,
                'type' => TransactionType::Transferred,
                'points' => -1000,
                'transfer_to' => $toUserId,
            ]);

            PointsTransaction::create([
                'user_id' => $toUserId,
                'tenant_id' => 1,
                'type' => TransactionType::Received,
                'points' => 1000,
                'transfer_from' => $fromUserId,
            ]);
        }

        $this->fraudControl->checkTransferFraud([
            'from_user_id' => $fromUserId,
            'to_user_id' => $toUserId,
            'transfer_count' => 20,
            'total_points' => 20000,
            'time_window_hours' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $fromUserId,
            FraudType::LoyaltyFraud,
            FraudSeverity::High
        );
    }
}
