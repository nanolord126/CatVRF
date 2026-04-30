<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Inventory\Models\InventoryCheck;

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
            InventoryCheck::class
        );
        $this->assertTrue($reflection->isFinal(), 'InventoryCheck must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new InventoryCheck();
        $this->assertNotEmpty($model->getFillable(), 'InventoryCheck must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new InventoryCheck();
        $this->assertNotEmpty($model->getCasts(), 'InventoryCheck must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new InventoryCheck();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
