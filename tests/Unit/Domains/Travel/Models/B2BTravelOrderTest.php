<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BTravelOrder model.
 *
 * @covers \App\Domains\Travel\Models\B2BTravelOrder
 */
final class B2BTravelOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Travel\Models\B2BTravelOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BTravelOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Travel\Models\B2BTravelOrder();
        $this->assertNotEmpty($model->getFillable(), 'B2BTravelOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Travel\Models\B2BTravelOrder();
        $this->assertNotEmpty($model->getCasts(), 'B2BTravelOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Travel\Models\B2BTravelOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
