<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Gardening\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GardenPlant model.
 *
 * @covers \App\Domains\Gardening\Models\GardenPlant
 */
final class GardenPlantTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Gardening\Models\GardenPlant::class
        );
        $this->assertTrue($reflection->isFinal(), 'GardenPlant must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Gardening\Models\GardenPlant();
        $this->assertNotEmpty($model->getFillable(), 'GardenPlant must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Gardening\Models\GardenPlant();
        $this->assertNotEmpty($model->getCasts(), 'GardenPlant must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Gardening\Models\GardenPlant();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
