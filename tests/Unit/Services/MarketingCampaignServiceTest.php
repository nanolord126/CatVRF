<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MarketingCampaignService.
 *
 * @covers \App\Services\Marketing\MarketingCampaignService
 */
final class MarketingCampaignServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\Marketing\MarketingCampaignService::class);
        $this->assertTrue($reflection->isFinal(), 'MarketingCampaignService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'MarketingCampaignService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\Marketing\MarketingCampaignService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createCampaign_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\MarketingCampaignService::class, 'createCampaign'),
            'MarketingCampaignService must implement createCampaign()'
        );
    }

    public function test_recordSpend_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\MarketingCampaignService::class, 'recordSpend'),
            'MarketingCampaignService must implement recordSpend()'
        );
    }

    public function test_pauseCampaign_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\MarketingCampaignService::class, 'pauseCampaign'),
            'MarketingCampaignService must implement pauseCampaign()'
        );
    }

    public function test_getActiveCampaigns_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\Marketing\MarketingCampaignService::class, 'getActiveCampaigns'),
            'MarketingCampaignService must implement getActiveCampaigns()'
        );
    }

}
