<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\InventoryService;

/**
 * Unit tests for InventoryService.
 *
 * @covers \App\Services\InventoryService
 */
final class InventoryServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(InventoryService::class);
        $this->assertTrue($reflection->isFinal(), 'InventoryService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'InventoryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(InventoryService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_decrease_inventory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'decreaseInventory'),
            'InventoryService must implement decreaseInventory()'
        );
    }

    public function test_increase_inventory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'increaseInventory'),
            'InventoryService must implement increaseInventory()'
        );
    }

    public function test_check_availability_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'checkAvailability'),
            'InventoryService must implement checkAvailability()'
        );
    }

    public function test_get_inventory_level_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'getInventoryLevel'),
            'InventoryService must implement getInventoryLevel()'
        );
    }

    public function test_is_low_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'isLow'),
            'InventoryService must implement isLow()'
        );
    }

    public function test_adjust_inventory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'adjustInventory'),
            'InventoryService must implement adjustInventory()'
        );
    }
}
