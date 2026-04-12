<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Gardening\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GardenCategory model.
 *
 * @covers \App\Domains\Gardening\Models\GardenCategory
 */
final class GardenCategoryTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Gardening\Models\GardenCategory::class
        );
        $this->assertTrue($reflection->isFinal(), 'GardenCategory must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Gardening\Models\GardenCategory();
        $this->assertNotEmpty($model->getFillable(), 'GardenCategory must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Gardening\Models\GardenCategory();
        $this->assertNotEmpty($model->getCasts(), 'GardenCategory must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Gardening\Models\GardenCategory();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
