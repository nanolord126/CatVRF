<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Auto\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AutoPart model.
 *
 * @covers \App\Domains\Auto\Models\AutoPart
 */
final class AutoPartTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Auto\Models\AutoPart::class
        );
        $this->assertTrue($reflection->isFinal(), 'AutoPart must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Auto\Models\AutoPart();
        $this->assertNotEmpty($model->getFillable(), 'AutoPart must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Auto\Models\AutoPart();
        $this->assertNotEmpty($model->getCasts(), 'AutoPart must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Auto\Models\AutoPart();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
