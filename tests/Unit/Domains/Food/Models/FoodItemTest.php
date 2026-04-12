<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Food\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FoodItem model.
 *
 * @covers \App\Domains\Food\Models\FoodItem
 */
final class FoodItemTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Models\FoodItem::class
        );
        $this->assertTrue($reflection->isFinal(), 'FoodItem must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Food\Models\FoodItem();
        $this->assertNotEmpty($model->getFillable(), 'FoodItem must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Food\Models\FoodItem();
        $this->assertNotEmpty($model->getCasts(), 'FoodItem must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Food\Models\FoodItem();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
