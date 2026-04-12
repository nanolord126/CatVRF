<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Delivery\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeliveryOrder model.
 *
 * @covers \App\Domains\Delivery\Models\DeliveryOrder
 */
final class DeliveryOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Delivery\Models\DeliveryOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'DeliveryOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Delivery\Models\DeliveryOrder();
        $this->assertNotEmpty($model->getFillable(), 'DeliveryOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Delivery\Models\DeliveryOrder();
        $this->assertNotEmpty($model->getCasts(), 'DeliveryOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Delivery\Models\DeliveryOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
