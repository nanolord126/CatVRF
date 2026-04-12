<?php declare(strict_types=1);

namespace Tests\Unit\Domains\MeatShops;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MeatShopService.
 *
 * @covers \App\Domains\MeatShops\Domain\Services\MeatShopService
 */
final class MeatShopServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Domain\Services\MeatShopService::class
        );
        $this->assertTrue($reflection->isFinal(), 'MeatShopService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Domain\Services\MeatShopService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'MeatShopService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Domain\Services\MeatShopService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'MeatShopService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createShop_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatShopService::class, 'createShop'),
            'MeatShopService must implement createShop()'
        );
    }

    public function test_addProduct_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatShopService::class, 'addProduct'),
            'MeatShopService must implement addProduct()'
        );
    }

    public function test_completeOrderPayout_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatShopService::class, 'completeOrderPayout'),
            'MeatShopService must implement completeOrderPayout()'
        );
    }

    public function test_getActiveShops_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatShopService::class, 'getActiveShops'),
            'MeatShopService must implement getActiveShops()'
        );
    }

}
