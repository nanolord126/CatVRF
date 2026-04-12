<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Flowers\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BFlowerOrder model.
 *
 * @covers \App\Domains\Flowers\Models\B2BFlowerOrder
 */
final class B2BFlowerOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Flowers\Models\B2BFlowerOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BFlowerOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Flowers\Models\B2BFlowerOrder();
        $this->assertNotEmpty($model->getFillable(), 'B2BFlowerOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Flowers\Models\B2BFlowerOrder();
        $this->assertNotEmpty($model->getCasts(), 'B2BFlowerOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Flowers\Models\B2BFlowerOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
