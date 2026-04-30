<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\MeatShops;

use PHPUnit\Framework\TestCase;
use App\Domains\MeatShops\Domain\Services\MeatShopService;

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
            MeatShopService::class
        );
        $this->assertTrue($reflection->isFinal(), 'MeatShopService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            MeatShopService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'MeatShopService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            MeatShopService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'MeatShopService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_shop_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatShopService::class, 'createShop'),
            'MeatShopService must implement createShop()'
        );
    }

    public function test_add_product_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatShopService::class, 'addProduct'),
            'MeatShopService must implement addProduct()'
        );
    }

    public function test_complete_order_payout_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatShopService::class, 'completeOrderPayout'),
            'MeatShopService must implement completeOrderPayout()'
        );
    }

    public function test_get_active_shops_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatShopService::class, 'getActiveShops'),
            'MeatShopService must implement getActiveShops()'
        );
    }
}
