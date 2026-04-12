<?php declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VeganProductService.
 *
 * @covers \App\Domains\VeganProducts\Domain\Services\VeganProductService
 */
final class VeganProductServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Domain\Services\VeganProductService::class
        );
        $this->assertTrue($reflection->isFinal(), 'VeganProductService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Domain\Services\VeganProductService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'VeganProductService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Domain\Services\VeganProductService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'VeganProductService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createProduct_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\VeganProducts\Domain\Services\VeganProductService::class, 'createProduct'),
            'VeganProductService must implement createProduct()'
        );
    }

    public function test_processOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\VeganProducts\Domain\Services\VeganProductService::class, 'processOrder'),
            'VeganProductService must implement processOrder()'
        );
    }

    public function test_adjustStock_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\VeganProducts\Domain\Services\VeganProductService::class, 'adjustStock'),
            'VeganProductService must implement adjustStock()'
        );
    }

    public function test_findSafeProducts_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\VeganProducts\Domain\Services\VeganProductService::class, 'findSafeProducts'),
            'VeganProductService must implement findSafeProducts()'
        );
    }

}
