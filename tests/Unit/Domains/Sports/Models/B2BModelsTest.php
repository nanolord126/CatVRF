<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Sports\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BModels model.
 *
 * @covers \App\Domains\Sports\Models\B2BModels
 */
final class B2BModelsTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Sports\Models\B2BModels::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BModels must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Sports\Models\B2BModels();
        $this->assertNotEmpty($model->getFillable(), 'B2BModels must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Sports\Models\B2BModels();
        $this->assertNotEmpty($model->getCasts(), 'B2BModels must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Sports\Models\B2BModels();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
