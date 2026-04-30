<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Restaurant\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Restaurant\Models\Restaurant;

/**
 * Unit tests for Restaurant model.
 *
 * @covers \App\Domains\Restaurant\Models\Restaurant
 */
final class RestaurantTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Restaurant::class
        );
        $this->assertTrue($reflection->isFinal(), 'Restaurant must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Restaurant();
        $this->assertNotEmpty($model->getFillable(), 'Restaurant must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
        $this->assertContains('category', $model->getFillable(), 'Must have category');
        $this->assertContains('cuisine_type', $model->getFillable(), 'Must have cuisine_type');
    }

    public function test_has_casts(): void
    {
        $model = new Restaurant();
        $this->assertNotEmpty($model->getCasts(), 'Restaurant must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Restaurant();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }

    public function test_has_category_constants(): void
    {
        $this->assertEquals('fine_dining', Restaurant::CATEGORY_FINE_DINING);
        $this->assertEquals('casual', Restaurant::CATEGORY_CASUAL);
        $this->assertEquals('fast_food', Restaurant::CATEGORY_FAST_FOOD);
        $this->assertEquals('cafe', Restaurant::CATEGORY_CAFE);
    }

    public function test_has_cuisine_type_constants(): void
    {
        $this->assertEquals('italian', Restaurant::CUISINE_ITALIAN);
        $this->assertEquals('japanese', Restaurant::CUISINE_JAPANESE);
        $this->assertEquals('chinese', Restaurant::CUISINE_CHINESE);
        $this->assertEquals('french', Restaurant::CUISINE_FRENCH);
    }
}
