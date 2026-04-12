<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Confectionery;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConfectioneryService.
 *
 * @covers \App\Domains\Confectionery\Domain\Services\ConfectioneryService
 */
final class ConfectioneryServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Confectionery\Domain\Services\ConfectioneryService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConfectioneryService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Confectionery\Domain\Services\ConfectioneryService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ConfectioneryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Confectionery\Domain\Services\ConfectioneryService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ConfectioneryService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_listShops_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Confectionery\Domain\Services\ConfectioneryService::class, 'listShops'),
            'ConfectioneryService must implement listShops()'
        );
    }

    public function test_getShopById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Confectionery\Domain\Services\ConfectioneryService::class, 'getShopById'),
            'ConfectioneryService must implement getShopById()'
        );
    }

    public function test_listProducts_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Confectionery\Domain\Services\ConfectioneryService::class, 'listProducts'),
            'ConfectioneryService must implement listProducts()'
        );
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Confectionery\Domain\Services\ConfectioneryService::class, 'createOrder'),
            'ConfectioneryService must implement createOrder()'
        );
    }

}
