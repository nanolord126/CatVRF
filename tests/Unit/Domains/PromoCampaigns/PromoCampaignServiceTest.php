<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\PromoCampaigns;

use PHPUnit\Framework\TestCase;
use App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService;

/**
 * Unit tests for PromoCampaignService.
 *
 * @covers \App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService
 */
final class PromoCampaignServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            PromoCampaignService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PromoCampaignService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            PromoCampaignService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PromoCampaignService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            PromoCampaignService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PromoCampaignService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_campaign_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PromoCampaignService::class, 'createCampaign'),
            'PromoCampaignService must implement createCampaign()'
        );
    }

    public function test_validate_promo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PromoCampaignService::class, 'validatePromo'),
            'PromoCampaignService must implement validatePromo()'
        );
    }

    public function test_apply_promo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PromoCampaignService::class, 'applyPromo'),
            'PromoCampaignService must implement applyPromo()'
        );
    }

    public function test_cancel_promo_use_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PromoCampaignService::class, 'cancelPromoUse'),
            'PromoCampaignService must implement cancelPromoUse()'
        );
    }

    public function test_get_active_campaigns_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PromoCampaignService::class, 'getActiveCampaigns'),
            'PromoCampaignService must implement getActiveCampaigns()'
        );
    }
}
