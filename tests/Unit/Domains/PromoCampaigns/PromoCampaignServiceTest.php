<?php declare(strict_types=1);

namespace Tests\Unit\Domains\PromoCampaigns;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PromoCampaignService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PromoCampaignService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PromoCampaignService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createCampaign_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class, 'createCampaign'),
            'PromoCampaignService must implement createCampaign()'
        );
    }

    public function test_validatePromo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class, 'validatePromo'),
            'PromoCampaignService must implement validatePromo()'
        );
    }

    public function test_applyPromo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class, 'applyPromo'),
            'PromoCampaignService must implement applyPromo()'
        );
    }

    public function test_cancelPromoUse_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class, 'cancelPromoUse'),
            'PromoCampaignService must implement cancelPromoUse()'
        );
    }

    public function test_getActiveCampaigns_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PromoCampaigns\Domain\Services\PromoCampaignService::class, 'getActiveCampaigns'),
            'PromoCampaignService must implement getActiveCampaigns()'
        );
    }

}
