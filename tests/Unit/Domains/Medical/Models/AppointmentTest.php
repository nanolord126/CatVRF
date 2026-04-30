<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Medical\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Medical\Models\Appointment;

/**
 * Unit tests for Appointment model.
 *
 * @covers \App\Domains\Medical\Models\Appointment
 */
final class AppointmentTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Appointment::class
        );
        $this->assertTrue($reflection->isFinal(), 'Appointment must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Appointment();
        $this->assertNotEmpty($model->getFillable(), 'Appointment must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new Appointment();
        $this->assertNotEmpty($model->getCasts(), 'Appointment must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Appointment();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
