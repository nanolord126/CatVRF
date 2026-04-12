<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ElectronicProduct model.
 *
 * @covers \App\Domains\Electronics\Models\ElectronicProduct
 */
final class ElectronicProductTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\Models\ElectronicProduct::class
        );
        $this->assertTrue($reflection->isFinal(), 'ElectronicProduct must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Electronics\Models\ElectronicProduct();
        $this->assertNotEmpty($model->getFillable(), 'ElectronicProduct must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Electronics\Models\ElectronicProduct();
        $this->assertNotEmpty($model->getCasts(), 'ElectronicProduct must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Electronics\Models\ElectronicProduct();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
