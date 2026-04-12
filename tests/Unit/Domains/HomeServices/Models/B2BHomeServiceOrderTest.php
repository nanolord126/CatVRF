<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HomeServices\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BHomeServiceOrder model.
 *
 * @covers \App\Domains\HomeServices\Models\B2BHomeServiceOrder
 */
final class B2BHomeServiceOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HomeServices\Models\B2BHomeServiceOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BHomeServiceOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\HomeServices\Models\B2BHomeServiceOrder();
        $this->assertNotEmpty($model->getFillable(), 'B2BHomeServiceOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\HomeServices\Models\B2BHomeServiceOrder();
        $this->assertNotEmpty($model->getCasts(), 'B2BHomeServiceOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\HomeServices\Models\B2BHomeServiceOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
