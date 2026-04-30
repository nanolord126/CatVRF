<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\GroceryAndDelivery;

use PHPUnit\Framework\TestCase;
use App\Domains\GroceryAndDelivery\Domain\Services\FastDeliveryService;

/**
 * Unit tests for FastDeliveryService.
 *
 * @covers \App\Domains\GroceryAndDelivery\Domain\Services\FastDeliveryService
 */
final class FastDeliveryServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            FastDeliveryService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FastDeliveryService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            FastDeliveryService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FastDeliveryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            FastDeliveryService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FastDeliveryService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_assign_delivery_partner_method_exists(): void
    {
        $this->assertTrue(
            method_exists(FastDeliveryService::class, 'assignDeliveryPartner'),
            'FastDeliveryService must implement assignDeliveryPartner()'
        );
    }
}
