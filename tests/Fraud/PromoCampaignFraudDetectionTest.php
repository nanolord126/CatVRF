<?php

declare(strict_types=1);

namespace Tests\Fraud;

use Modules\PromoCampaign\Domain\Entities\Campaign;
use Modules\PromoCampaign\Domain\Entities\CampaignParticipation;
use Illuminate\Support\Facades\Cache;

final class PromoCampaignFraudDetectionTest extends BaseFraudTest
{
    public function test_fake_participation(): void
    {
        $campaignId = 1;
        $deviceFingerprint = 'fp_campaign_bot';

        for ($i = 0; $i < 100; $i++) {
            CampaignParticipation::create([
                'campaign_id' => $campaignId,
                'user_id' => $i + 1000,
                'device_fingerprint' => $deviceFingerprint,
                'participated_at' => now(),
            ]);
        }

        $this->fraudControl->checkCampaignFraud([
            'campaign_id' => $campaignId,
            'device_fingerprint' => $deviceFingerprint,
            'participation_count' => 100,
            'unique_users' => 1, // Same device
        ]);

        $this->assertFraudAlertCreated(
            'campaign',
            $campaignId,
            FraudType::CampaignFraud,
            FraudSeverity::High
        );
    }

    public function test_budget_manipulation(): void
    {
        $campaignId = 1;
        $originalBudget = 100000;

        $campaign = Campaign::find($campaignId);
        $campaign->update(['budget' => 10000000]); // 100x increase

        $this->fraudControl->checkBudgetManipulation([
            'campaign_id' => $campaignId,
            'original_budget' => $originalBudget,
            'new_budget' => 10000000,
            'change_reason' => null,
            'approved_by' => null,
        ]);

        $this->assertFraudAlertCreated(
            'campaign',
            $campaignId,
            FraudType::FinancialFraud,
            FraudSeverity::Critical
        );
    }

    public function test_fake_conversion(): void
    {
        $campaignId = 1;

        for ($i = 0; $i => 50; $i++) {
            $this->fraudControl->checkConversionFraud([
                'campaign_id' => $campaignId,
                'user_id' => $i + 1000,
                'conversion_value' => 10000,
                'ip_address' => '192.168.1.100',
                'device_fingerprint' => 'fp_conversion_bot',
                'actual_purchase' => false,
            ]);
        }

        $this->assertFraudAlertCreated(
            'campaign',
            $campaignId,
            FraudType::CampaignFraud,
            FraudSeverity::Critical
        );
    }

    public function test_click_fraud(): void
    {
        $campaignId = 1;
        $ipAddress = '192.168.1.101';

        for ($i = 0; $i < 500; $i++) {
            $this->fraudControl->checkClickFraud([
                'campaign_id' => $campaignId,
                'ip_address' => $ipAddress,
                'user_agent' => 'bot/1.0',
                'click_count' => 500,
                'time_window_minutes' => 10,
            ]);
        }

        $this->assertFraudAlertCreated(
            'campaign',
            $campaignId,
            FraudType::ClickFraud,
            FraudSeverity::Critical
        );
    }

    public function test_impression_fraud(): void
    {
        $campaignId = 1;
        $deviceFingerprint = 'fp_impression_bot';

        for ($i = 0; $i < 10000; $i++) {
            $this->fraudControl->checkImpressionFraud([
                'campaign_id' => $campaignId,
                'device_fingerprint' => $deviceFingerprint,
                'impression_count' => 10000,
                'time_window_hours' => 1,
                'viewable_percentage' => 0, // Not actually viewed
            ]);
        }

        $this->assertFraudAlertCreated(
            'campaign',
            $campaignId,
            FraudType::ImpressionFraud,
            FraudSeverity::High
        );
    }
}
