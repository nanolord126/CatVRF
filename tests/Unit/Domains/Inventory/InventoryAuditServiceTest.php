<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory;

use PHPUnit\Framework\TestCase;
use App\Domains\Inventory\Domain\Services\InventoryAuditService;

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
            InventoryAuditService::class
        );
        $this->assertTrue($reflection->isFinal(), 'InventoryAuditService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            InventoryAuditService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'InventoryAuditService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            InventoryAuditService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'InventoryAuditService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_start_audit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryAuditService::class, 'startAudit'),
            'InventoryAuditService must implement startAudit()'
        );
    }

    public function test_complete_audit_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryAuditService::class, 'completeAudit'),
            'InventoryAuditService must implement completeAudit()'
        );
    }
}
