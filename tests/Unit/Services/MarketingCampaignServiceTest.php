<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Marketing\MarketingCampaignService;

/**
 * Unit tests for MarketingCampaignService.
 *
 * @covers \App\Services\Marketing\MarketingCampaignService
 */
final class MarketingCampaignServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(MarketingCampaignService::class);
        $this->assertTrue($reflection->isFinal(), 'MarketingCampaignService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'MarketingCampaignService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(MarketingCampaignService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_campaign_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MarketingCampaignService::class, 'createCampaign'),
            'MarketingCampaignService must implement createCampaign()'
        );
    }

    public function test_record_spend_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MarketingCampaignService::class, 'recordSpend'),
            'MarketingCampaignService must implement recordSpend()'
        );
    }

    public function test_pause_campaign_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MarketingCampaignService::class, 'pauseCampaign'),
            'MarketingCampaignService must implement pauseCampaign()'
        );
    }

    public function test_get_active_campaigns_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MarketingCampaignService::class, 'getActiveCampaigns'),
            'MarketingCampaignService must implement getActiveCampaigns()'
        );
    }
}
