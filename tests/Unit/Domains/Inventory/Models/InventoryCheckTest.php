<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InventoryCheck model.
 *
 * @covers \App\Domains\Inventory\Models\InventoryCheck
 */
final class InventoryCheckTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\Models\InventoryCheck::class
        );
        $this->assertTrue($reflection->isFinal(), 'InventoryCheck must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Inventory\Models\InventoryCheck();
        $this->assertNotEmpty($model->getFillable(), 'InventoryCheck must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Inventory\Models\InventoryCheck();
        $this->assertNotEmpty($model->getCasts(), 'InventoryCheck must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Inventory\Models\InventoryCheck();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
