<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ElectronicOrder model.
 *
 * @covers \App\Domains\Electronics\Models\ElectronicOrder
 */
final class ElectronicOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\Models\ElectronicOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'ElectronicOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Electronics\Models\ElectronicOrder();
        $this->assertNotEmpty($model->getFillable(), 'ElectronicOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Electronics\Models\ElectronicOrder();
        $this->assertNotEmpty($model->getCasts(), 'ElectronicOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Electronics\Models\ElectronicOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
