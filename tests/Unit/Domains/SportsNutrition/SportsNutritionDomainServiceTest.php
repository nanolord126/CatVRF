<?php declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SportsNutritionDomainService.
 *
 * @covers \App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService
 */
final class SportsNutritionDomainServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class
        );
        $this->assertTrue($reflection->isFinal(), 'SportsNutritionDomainService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'SportsNutritionDomainService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'SportsNutritionDomainService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_saveProduct_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class, 'saveProduct'),
            'SportsNutritionDomainService must implement saveProduct()'
        );
    }

    public function test_useConsumable_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class, 'useConsumable'),
            'SportsNutritionDomainService must implement useConsumable()'
        );
    }

    public function test_createSubscriptionBox_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class, 'createSubscriptionBox'),
            'SportsNutritionDomainService must implement createSubscriptionBox()'
        );
    }

    public function test_calculateWholesalePrice_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\SportsNutrition\Domain\Services\SportsNutritionDomainService::class, 'calculateWholesalePrice'),
            'SportsNutritionDomainService must implement calculateWholesalePrice()'
        );
    }

}
