<?php declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for VeganModels model.
 *
 * @covers \App\Domains\VeganProducts\Models\VeganModels
 */
final class VeganModelsTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\Models\VeganModels::class
        );
        $this->assertTrue($reflection->isFinal(), 'VeganModels must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\VeganProducts\Models\VeganModels();
        $this->assertNotEmpty($model->getFillable(), 'VeganModels must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\VeganProducts\Models\VeganModels();
        $this->assertNotEmpty($model->getCasts(), 'VeganModels must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\VeganProducts\Models\VeganModels();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
