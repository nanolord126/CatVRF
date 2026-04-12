<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Logistics\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BLogisticsStorefront model.
 *
 * @covers \App\Domains\Logistics\Models\B2BLogisticsStorefront
 */
final class B2BLogisticsStorefrontTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Models\B2BLogisticsStorefront::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BLogisticsStorefront must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Logistics\Models\B2BLogisticsStorefront();
        $this->assertNotEmpty($model->getFillable(), 'B2BLogisticsStorefront must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Logistics\Models\B2BLogisticsStorefront();
        $this->assertNotEmpty($model->getCasts(), 'B2BLogisticsStorefront must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Logistics\Models\B2BLogisticsStorefront();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
