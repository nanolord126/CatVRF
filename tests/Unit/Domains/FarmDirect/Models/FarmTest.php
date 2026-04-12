<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FarmDirect\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Farm model.
 *
 * @covers \App\Domains\FarmDirect\Models\Farm
 */
final class FarmTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Models\Farm::class
        );
        $this->assertTrue($reflection->isFinal(), 'Farm must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\FarmDirect\Models\Farm();
        $this->assertNotEmpty($model->getFillable(), 'Farm must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\FarmDirect\Models\Farm();
        $this->assertNotEmpty($model->getCasts(), 'Farm must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\FarmDirect\Models\Farm();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
