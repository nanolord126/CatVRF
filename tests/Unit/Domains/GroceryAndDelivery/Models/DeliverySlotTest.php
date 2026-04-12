<?php declare(strict_types=1);

namespace Tests\Unit\Domains\GroceryAndDelivery\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeliverySlot model.
 *
 * @covers \App\Domains\GroceryAndDelivery\Models\DeliverySlot
 */
final class DeliverySlotTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GroceryAndDelivery\Models\DeliverySlot::class
        );
        $this->assertTrue($reflection->isFinal(), 'DeliverySlot must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\GroceryAndDelivery\Models\DeliverySlot();
        $this->assertNotEmpty($model->getFillable(), 'DeliverySlot must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\GroceryAndDelivery\Models\DeliverySlot();
        $this->assertNotEmpty($model->getCasts(), 'DeliverySlot must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\GroceryAndDelivery\Models\DeliverySlot();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
