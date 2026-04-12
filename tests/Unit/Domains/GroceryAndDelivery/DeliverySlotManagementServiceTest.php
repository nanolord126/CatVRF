<?php declare(strict_types=1);

namespace Tests\Unit\Domains\GroceryAndDelivery;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeliverySlotManagementService.
 *
 * @covers \App\Domains\GroceryAndDelivery\Domain\Services\DeliverySlotManagementService
 */
final class DeliverySlotManagementServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GroceryAndDelivery\Domain\Services\DeliverySlotManagementService::class
        );
        $this->assertTrue($reflection->isFinal(), 'DeliverySlotManagementService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GroceryAndDelivery\Domain\Services\DeliverySlotManagementService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'DeliverySlotManagementService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GroceryAndDelivery\Domain\Services\DeliverySlotManagementService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'DeliverySlotManagementService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_getAvailableSlots_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GroceryAndDelivery\Domain\Services\DeliverySlotManagementService::class, 'getAvailableSlots'),
            'DeliverySlotManagementService must implement getAvailableSlots()'
        );
    }

    public function test_updateSurgeMultiplier_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GroceryAndDelivery\Domain\Services\DeliverySlotManagementService::class, 'updateSurgeMultiplier'),
            'DeliverySlotManagementService must implement updateSurgeMultiplier()'
        );
    }

}
