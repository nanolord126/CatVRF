<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BContract model.
 *
 * @covers \App\Domains\Hotels\Models\B2BContract
 */
final class B2BContractTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\Models\B2BContract::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BContract must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Hotels\Models\B2BContract();
        $this->assertNotEmpty($model->getFillable(), 'B2BContract must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Hotels\Models\B2BContract();
        $this->assertNotEmpty($model->getCasts(), 'B2BContract must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Hotels\Models\B2BContract();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
