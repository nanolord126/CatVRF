<?php declare(strict_types=1);

namespace Tests\Unit\Domains\MeatShops;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MeatService.
 *
 * @covers \App\Domains\MeatShops\Domain\Services\MeatService
 */
final class MeatServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Domain\Services\MeatService::class
        );
        $this->assertTrue($reflection->isFinal(), 'MeatService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Domain\Services\MeatService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'MeatService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Domain\Services\MeatService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'MeatService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatService::class, 'createOrder'),
            'MeatService must implement createOrder()'
        );
    }

    public function test_readyForDelivery_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatService::class, 'readyForDelivery'),
            'MeatService must implement readyForDelivery()'
        );
    }

    public function test_finalizePayout_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\MeatShops\Domain\Services\MeatService::class, 'finalizePayout'),
            'MeatService must implement finalizePayout()'
        );
    }

}
