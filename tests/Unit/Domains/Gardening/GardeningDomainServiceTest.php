<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Gardening;

use PHPUnit\Framework\TestCase;
use App\Domains\Gardening\Domain\Services\GardeningDomainService;

/**
 * Unit tests for GardeningDomainService.
 *
 * @covers \App\Domains\Gardening\Domain\Services\GardeningDomainService
 */
final class GardeningDomainServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            GardeningDomainService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GardeningDomainService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            GardeningDomainService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GardeningDomainService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            GardeningDomainService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GardeningDomainService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_save_product_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GardeningDomainService::class, 'saveProduct'),
            'GardeningDomainService must implement saveProduct()'
        );
    }

    public function test_update_subscription_box_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GardeningDomainService::class, 'updateSubscriptionBox'),
            'GardeningDomainService must implement updateSubscriptionBox()'
        );
    }

    public function test_get_landscaper_pricing_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GardeningDomainService::class, 'getLandscaperPricing'),
            'GardeningDomainService must implement getLandscaperPricing()'
        );
    }

    public function test_is_plant_in_season_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GardeningDomainService::class, 'isPlantInSeason'),
            'GardeningDomainService must implement isPlantInSeason()'
        );
    }
}
