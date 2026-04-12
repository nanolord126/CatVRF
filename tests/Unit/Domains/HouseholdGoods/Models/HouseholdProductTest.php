<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HouseholdGoods\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HouseholdProduct model.
 *
 * @covers \App\Domains\HouseholdGoods\Models\HouseholdProduct
 */
final class HouseholdProductTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HouseholdGoods\Models\HouseholdProduct::class
        );
        $this->assertTrue($reflection->isFinal(), 'HouseholdProduct must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\HouseholdGoods\Models\HouseholdProduct();
        $this->assertNotEmpty($model->getFillable(), 'HouseholdProduct must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\HouseholdGoods\Models\HouseholdProduct();
        $this->assertNotEmpty($model->getCasts(), 'HouseholdProduct must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\HouseholdGoods\Models\HouseholdProduct();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
