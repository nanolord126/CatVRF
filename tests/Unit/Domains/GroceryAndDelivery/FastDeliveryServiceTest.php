<?php declare(strict_types=1);

namespace Tests\Unit\Domains\GroceryAndDelivery;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\GroceryAndDelivery\Domain\Services\FastDeliveryService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FastDeliveryService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GroceryAndDelivery\Domain\Services\FastDeliveryService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FastDeliveryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GroceryAndDelivery\Domain\Services\FastDeliveryService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FastDeliveryService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_assignDeliveryPartner_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GroceryAndDelivery\Domain\Services\FastDeliveryService::class, 'assignDeliveryPartner'),
            'FastDeliveryService must implement assignDeliveryPartner()'
        );
    }

}
