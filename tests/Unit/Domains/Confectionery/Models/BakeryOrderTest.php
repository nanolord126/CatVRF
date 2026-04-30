<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Confectionery\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Confectionery\Models\BakeryOrder;

/**
 * Unit tests for BakeryOrder model.
 *
 * @covers \App\Domains\Confectionery\Models\BakeryOrder
 */
final class BakeryOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            BakeryOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'BakeryOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new BakeryOrder();
        $this->assertNotEmpty($model->getFillable(), 'BakeryOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new BakeryOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
