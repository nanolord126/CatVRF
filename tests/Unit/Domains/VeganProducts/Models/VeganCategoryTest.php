<?php declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VeganCategory model.
 *
 * @covers \App\Domains\VeganProducts\Models\VeganCategory
 */
final class VeganCategoryTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Models\VeganCategory::class
        );
        $this->assertTrue($reflection->isFinal(), 'VeganCategory must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\VeganProducts\Models\VeganCategory();
        $this->assertNotEmpty($model->getFillable(), 'VeganCategory must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\VeganProducts\Models\VeganCategory();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
