<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Sports\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BSportOrder model.
 *
 * @covers \App\Domains\Sports\Models\B2BSportOrder
 */
final class B2BSportOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Sports\Models\B2BSportOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BSportOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Sports\Models\B2BSportOrder();
        $this->assertNotEmpty($model->getFillable(), 'B2BSportOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Sports\Models\B2BSportOrder();
        $this->assertNotEmpty($model->getCasts(), 'B2BSportOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Sports\Models\B2BSportOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
