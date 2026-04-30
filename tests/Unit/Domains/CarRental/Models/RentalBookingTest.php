<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\CarRental\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\CarRental\Models\RentalBooking;

/**
 * Unit tests for RentalBooking model.
 *
 * @covers \App\Domains\CarRental\Models\RentalBooking
 */
final class RentalBookingTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            RentalBooking::class
        );
        $this->assertTrue($reflection->isFinal(), 'RentalBooking must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new RentalBooking();
        $this->assertNotEmpty($model->getFillable(), 'RentalBooking must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new RentalBooking();
        $this->assertNotEmpty($model->getCasts(), 'RentalBooking must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new RentalBooking();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
