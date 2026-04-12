<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Furniture\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FurnitureCategory model.
 *
 * @covers \App\Domains\Furniture\Models\FurnitureCategory
 */
final class FurnitureCategoryTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Models\FurnitureCategory::class
        );
        $this->assertTrue($reflection->isFinal(), 'FurnitureCategory must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Furniture\Models\FurnitureCategory();
        $this->assertNotEmpty($model->getFillable(), 'FurnitureCategory must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Furniture\Models\FurnitureCategory();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
