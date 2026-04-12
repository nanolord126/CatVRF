<?php declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SportsNutritionConsumable model.
 *
 * @covers \App\Domains\SportsNutrition\Models\SportsNutritionConsumable
 */
final class SportsNutritionConsumableTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\Models\SportsNutritionConsumable::class
        );
        $this->assertTrue($reflection->isFinal(), 'SportsNutritionConsumable must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\SportsNutrition\Models\SportsNutritionConsumable();
        $this->assertNotEmpty($model->getFillable(), 'SportsNutritionConsumable must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\SportsNutrition\Models\SportsNutritionConsumable();
        $this->assertNotEmpty($model->getCasts(), 'SportsNutritionConsumable must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\SportsNutrition\Models\SportsNutritionConsumable();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
