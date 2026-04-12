<?php declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryService.
 *
 * @covers \App\Services\InventoryService
 */
final class InventoryServiceTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(\App\Services\InventoryService::class);
        $this->assertTrue($reflection->isFinal(), 'InventoryService must be final');
        $this->assertTrue($reflection->isReadOnly(), 'InventoryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(\App\Services\InventoryService::class);
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_decreaseInventory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\InventoryService::class, 'decreaseInventory'),
            'InventoryService must implement decreaseInventory()'
        );
    }

    public function test_increaseInventory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\InventoryService::class, 'increaseInventory'),
            'InventoryService must implement increaseInventory()'
        );
    }

    public function test_checkAvailability_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\InventoryService::class, 'checkAvailability'),
            'InventoryService must implement checkAvailability()'
        );
    }

    public function test_getInventoryLevel_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\InventoryService::class, 'getInventoryLevel'),
            'InventoryService must implement getInventoryLevel()'
        );
    }

    public function test_isLow_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\InventoryService::class, 'isLow'),
            'InventoryService must implement isLow()'
        );
    }

    public function test_adjustInventory_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Services\InventoryService::class, 'adjustInventory'),
            'InventoryService must implement adjustInventory()'
        );
    }

}
