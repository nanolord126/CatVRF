<?php

declare(strict_types=1);

namespace Tests\Fraud;

final class CommissionsFraudDetectionTest extends BaseFraudTest
{
    public function test_commission_inflation(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'commission_inflation',
            'user_id' => $userId,
            'original_rate' => 5.0,
            'inflated_rate' => 50.0,
            'transaction_amount' => 1000000,
            'correlation_id' => 'test_commissions_001',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FinancialFraud,
            FraudSeverity::Critical
        );
    }

    public function test_fake_referral_fraud(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'fake_referral',
            'user_id' => $userId,
            'referral_count' => 500,
            'time_window_days' => 1,
            'suspicious_patterns' => true,
            'same_ip_referrals' => 450,
            'correlation_id' => 'test_commissions_002',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ReferralFraud,
            FraudSeverity::Critical
        );
    }

    public function test_self_referral(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'self_referral',
            'user_id' => $userId,
            'referrer_id' => $userId,
            'same_device_fingerprint' => true,
            'correlation_id' => 'test_commissions_003',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::ReferralFraud,
            FraudSeverity::High
        );
    }

    public function test_commission_circular_payments(): void
    {
        $userId = 1;

        $this->fraudControl->check([
            'operation_type' => 'circular_commission',
            'user_id' => $userId,
            'payment_chain' => [1, 2, 3, 1],
            'circular_detected' => true,
            'correlation_id' => 'test_commissions_004',
        ]);

        $this->assertFraudAlertCreated(
            'user',
            $userId,
            FraudType::FinancialFraud,
            FraudSeverity::Critical
        );
    }

    public function test_unauthorized_commission_adjustment(): void
    {
        $userId = 1;
        $commissionId = 1000;

        $this->fraudControl->check([
            'operation_type' => 'unauthorized_adjustment',
            'user_id' => $userId,
            'commission_id' => $commissionId,
            'permission_level' => 'agent',
            'required_level' => 'admin',
            'adjustment_amount' => 50000,
            'correlation_id' => 'test_commissions_005',
        ]);

        $this->assertFraudAlertCreated(
            'commission',
            $commissionId,
            FraudType::UnauthorizedAccess,
            FraudSeverity::High
        );
    }
}
