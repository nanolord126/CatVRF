<?php declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SportsNutritionCategory model.
 *
 * @covers \App\Domains\SportsNutrition\Models\SportsNutritionCategory
 */
final class SportsNutritionCategoryTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Models\SportsNutritionCategory::class
        );
        $this->assertTrue($reflection->isFinal(), 'SportsNutritionCategory must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\SportsNutrition\Models\SportsNutritionCategory();
        $this->assertNotEmpty($model->getFillable(), 'SportsNutritionCategory must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\SportsNutrition\Models\SportsNutritionCategory();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
