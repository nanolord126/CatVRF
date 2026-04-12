<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ConstructionAndRepair\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConstructionProject model.
 *
 * @covers \App\Domains\ConstructionAndRepair\Models\ConstructionProject
 */
final class ConstructionProjectTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ConstructionAndRepair\Models\ConstructionProject::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConstructionProject must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\ConstructionAndRepair\Models\ConstructionProject();
        $this->assertNotEmpty($model->getFillable(), 'ConstructionProject must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\ConstructionAndRepair\Models\ConstructionProject();
        $this->assertNotEmpty($model->getCasts(), 'ConstructionProject must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\ConstructionAndRepair\Models\ConstructionProject();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
