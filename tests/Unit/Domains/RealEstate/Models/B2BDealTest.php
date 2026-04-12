<?php declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BDeal model.
 *
 * @covers \App\Domains\RealEstate\Models\B2BDeal
 */
final class B2BDealTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\RealEstate\Models\B2BDeal::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BDeal must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\RealEstate\Models\B2BDeal();
        $this->assertNotEmpty($model->getFillable(), 'B2BDeal must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\RealEstate\Models\B2BDeal();
        $this->assertNotEmpty($model->getCasts(), 'B2BDeal must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\RealEstate\Models\B2BDeal();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
