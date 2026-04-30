<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\Promo\Domain\Entities\PromoCode;
use Modules\Promo\Domain\Entities\PromoUsage;
use Illuminate\Support\Facades\Cache;

final class PromoFraudDetectionTest extends BaseFraudTest
{
    public function test_promo_code_abuse(): void
    {
        $userId = 1;
        $promoCode = 'SAVE50';

        for ($i = 0; $i < 15; $i++) {
            PromoUsage::create([
                'user_id' => $userId,
                'promo_code' => $promoCode,
                'discount_amount' => 500,
                'order_amount' => 2000,
            ]);
        }

        $this->fraudControl->checkCouponAbuse([
            'user_id' => $userId,
            'promo_code' => $promoCode,
            'usage_count' => 15,
            'max_uses_per_user' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CouponAbuse,
            FraudSeverity::High
        );
    }

    public function test_fake_promo_creation(): void
    {
        $userId = 1;

        for ($i = 0; $i < 20; $i++) {
            PromoCode::create([
                'created_by' => $userId,
                'tenant_id' => 1,
                'code' => "FAKE{$i}",
                'discount_type' => 'percentage',
                'discount_value' => 100, // 100% off
                'max_uses' => 999999,
                'status' => 'active',
            ]);
        }

        $this->fraudControl->checkPromoFraud([
            'user_id' => $userId,
            'promo_count' => 20,
            'avg_discount' => 100,
            'time_window_hours' => 24,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::PromoAbuse,
            FraudSeverity::Critical
        );
    }

    public function test_referral_code_manipulation(): void
    {
        $referrerId = 1;
        $deviceFingerprint = 'fp_referral_bot';

        for ($i = 0; $i < 50; $i++) {
            $this->fraudControl->checkReferralFraud([
                'referrer_id' => $referrerId,
                'referred_user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'conversion_rate' => 0,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $referrerId,
            FraudType::ReferralFraud,
            FraudSeverity::High
        );
    }

    public function test_promo_stacking(): void
    {
        $userId = 1;

        $this->fraudControl->checkPromoStacking([
            'user_id' => $userId,
            'order_amount' => 10000,
            'promo_codes_used' => 5,
            'total_discount' => 9000, // 90% discount
            'max_allowed_promos' => 1,
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CouponAbuse,
            FraudSeverity::Medium
        );
    }

    public function test_expired_promo_usage(): void
    {
        $userId = 1;
        $promoCode = 'EXPIRED2023';

        for ($i = 0; $i < 10; $i++) {
            $this->fraudControl->checkExpiredPromoUsage([
                'user_id' => $userId,
                'promo_code' => $promoCode,
                'expiration_date' => now()->subYear(),
                'attempt_count' => 10,
            ]);
        }

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::CouponAbuse,
            FraudSeverity::Low
        );
    }
}
