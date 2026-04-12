<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Gardening;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Gardening\Domain\Services\GardeningDomainService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GardeningDomainService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Gardening\Domain\Services\GardeningDomainService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GardeningDomainService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Gardening\Domain\Services\GardeningDomainService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GardeningDomainService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_saveProduct_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Gardening\Domain\Services\GardeningDomainService::class, 'saveProduct'),
            'GardeningDomainService must implement saveProduct()'
        );
    }

    public function test_updateSubscriptionBox_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Gardening\Domain\Services\GardeningDomainService::class, 'updateSubscriptionBox'),
            'GardeningDomainService must implement updateSubscriptionBox()'
        );
    }

    public function test_getLandscaperPricing_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Gardening\Domain\Services\GardeningDomainService::class, 'getLandscaperPricing'),
            'GardeningDomainService must implement getLandscaperPricing()'
        );
    }

    public function test_isPlantInSeason_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Gardening\Domain\Services\GardeningDomainService::class, 'isPlantInSeason'),
            'GardeningDomainService must implement isPlantInSeason()'
        );
    }

}
