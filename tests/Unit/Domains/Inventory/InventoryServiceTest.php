<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryService.
 *
 * @covers \App\Domains\Inventory\Domain\Services\InventoryService
 */
final class InventoryServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Domain\Services\InventoryService::class
        );
        $this->assertTrue($reflection->isFinal(), 'InventoryService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Domain\Services\InventoryService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'InventoryService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Domain\Services\InventoryService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'InventoryService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_reserve_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryService::class, 'reserve'),
            'InventoryService must implement reserve()'
        );
    }

    public function test_releaseReservation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryService::class, 'releaseReservation'),
            'InventoryService must implement releaseReservation()'
        );
    }

    public function test_addStock_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryService::class, 'addStock'),
            'InventoryService must implement addStock()'
        );
    }

    public function test_deductStock_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryService::class, 'deductStock'),
            'InventoryService must implement deductStock()'
        );
    }

    public function test_adjust_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryService::class, 'adjust'),
            'InventoryService must implement adjust()'
        );
    }

}
