<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ShortTermRentals\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApartmentBooking model.
 *
 * @covers \App\Domains\ShortTermRentals\Models\ApartmentBooking
 */
final class ApartmentBookingTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Models\ApartmentBooking::class
        );
        $this->assertTrue($reflection->isFinal(), 'ApartmentBooking must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\ShortTermRentals\Models\ApartmentBooking();
        $this->assertNotEmpty($model->getFillable(), 'ApartmentBooking must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\ShortTermRentals\Models\ApartmentBooking();
        $this->assertNotEmpty($model->getCasts(), 'ApartmentBooking must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\ShortTermRentals\Models\ApartmentBooking();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
