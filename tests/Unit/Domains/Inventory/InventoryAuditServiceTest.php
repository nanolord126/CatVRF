<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryAuditService.
 *
 * @covers \App\Domains\Inventory\Domain\Services\InventoryAuditService
 */
final class InventoryAuditServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Domain\Services\InventoryAuditService::class
        );
        $this->assertTrue($reflection->isFinal(), 'InventoryAuditService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Domain\Services\InventoryAuditService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'InventoryAuditService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Domain\Services\InventoryAuditService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'InventoryAuditService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_startAudit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryAuditService::class, 'startAudit'),
            'InventoryAuditService must implement startAudit()'
        );
    }

    public function test_completeAudit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Inventory\Domain\Services\InventoryAuditService::class, 'completeAudit'),
            'InventoryAuditService must implement completeAudit()'
        );
    }

}
