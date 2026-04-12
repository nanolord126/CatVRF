<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Logistics\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BLogisticsOrder model.
 *
 * @covers \App\Domains\Logistics\Models\B2BLogisticsOrder
 */
final class B2BLogisticsOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Models\B2BLogisticsOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BLogisticsOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Logistics\Models\B2BLogisticsOrder();
        $this->assertNotEmpty($model->getFillable(), 'B2BLogisticsOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Logistics\Models\B2BLogisticsOrder();
        $this->assertNotEmpty($model->getCasts(), 'B2BLogisticsOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Logistics\Models\B2BLogisticsOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
