<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Confectionery\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Cake model.
 *
 * @covers \App\Domains\Confectionery\Models\Cake
 */
final class CakeTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Confectionery\Models\Cake::class
        );
        $this->assertTrue($reflection->isFinal(), 'Cake must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Confectionery\Models\Cake();
        $this->assertNotEmpty($model->getFillable(), 'Cake must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Confectionery\Models\Cake();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
