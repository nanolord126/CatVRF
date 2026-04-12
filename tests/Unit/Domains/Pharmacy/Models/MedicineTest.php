<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Pharmacy\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Medicine model.
 *
 * @covers \App\Domains\Pharmacy\Models\Medicine
 */
final class MedicineTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Pharmacy\Models\Medicine::class
        );
        $this->assertTrue($reflection->isFinal(), 'Medicine must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Pharmacy\Models\Medicine();
        $this->assertNotEmpty($model->getFillable(), 'Medicine must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Pharmacy\Models\Medicine();
        $this->assertNotEmpty($model->getCasts(), 'Medicine must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Pharmacy\Models\Medicine();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
