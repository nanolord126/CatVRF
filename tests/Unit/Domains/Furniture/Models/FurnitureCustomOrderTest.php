<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Furniture\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Furniture\Models\FurnitureCustomOrder;

/**
 * Unit tests for FurnitureCustomOrder model.
 *
 * @covers \App\Domains\Furniture\Models\FurnitureCustomOrder
 */
final class FurnitureCustomOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            FurnitureCustomOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'FurnitureCustomOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new FurnitureCustomOrder();
        $this->assertNotEmpty($model->getFillable(), 'FurnitureCustomOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new FurnitureCustomOrder();
        $this->assertNotEmpty($model->getCasts(), 'FurnitureCustomOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new FurnitureCustomOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
