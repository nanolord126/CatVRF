<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\MeatShops;

use PHPUnit\Framework\TestCase;
use App\Domains\MeatShops\Domain\Services\MeatService;

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
            MeatService::class
        );
        $this->assertTrue($reflection->isFinal(), 'MeatService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            MeatService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'MeatService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            MeatService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'MeatService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_order_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatService::class, 'createOrder'),
            'MeatService must implement createOrder()'
        );
    }

    public function test_ready_for_delivery_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatService::class, 'readyForDelivery'),
            'MeatService must implement readyForDelivery()'
        );
    }

    public function test_finalize_payout_method_exists(): void
    {
        $this->assertTrue(
            method_exists(MeatService::class, 'finalizePayout'),
            'MeatService must implement finalizePayout()'
        );
    }
}
