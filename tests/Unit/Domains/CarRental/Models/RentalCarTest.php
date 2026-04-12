<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CarRental\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RentalCar model.
 *
 * @covers \App\Domains\CarRental\Models\RentalCar
 */
final class RentalCarTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CarRental\Models\RentalCar::class
        );
        $this->assertTrue($reflection->isFinal(), 'RentalCar must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\CarRental\Models\RentalCar();
        $this->assertNotEmpty($model->getFillable(), 'RentalCar must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\CarRental\Models\RentalCar();
        $this->assertNotEmpty($model->getCasts(), 'RentalCar must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\CarRental\Models\RentalCar();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
