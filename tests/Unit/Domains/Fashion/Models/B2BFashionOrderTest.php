<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fashion\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BFashionOrder model.
 *
 * @covers \App\Domains\Fashion\Models\B2BFashionOrder
 */
final class B2BFashionOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fashion\Models\B2BFashionOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BFashionOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Fashion\Models\B2BFashionOrder();
        $this->assertNotEmpty($model->getFillable(), 'B2BFashionOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Fashion\Models\B2BFashionOrder();
        $this->assertNotEmpty($model->getCasts(), 'B2BFashionOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Fashion\Models\B2BFashionOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
