<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Inventory\Models\InventoryItem;

/**
 * Unit tests for InventoryItem model.
 *
 * @covers \App\Domains\Inventory\Models\InventoryItem
 */
final class InventoryItemTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            InventoryItem::class
        );
        $this->assertTrue($reflection->isFinal(), 'InventoryItem must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new InventoryItem();
        $this->assertNotEmpty($model->getFillable(), 'InventoryItem must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new InventoryItem();
        $this->assertNotEmpty($model->getCasts(), 'InventoryItem must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new InventoryItem();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
