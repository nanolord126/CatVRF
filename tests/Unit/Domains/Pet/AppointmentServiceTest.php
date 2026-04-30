<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Pet;

use PHPUnit\Framework\TestCase;
use App\Domains\Pet\Domain\Services\AppointmentService;

/**
 * Unit tests for AppointmentService.
 *
 * @covers \App\Domains\Pet\Domain\Services\AppointmentService
 */
final class AppointmentServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AppointmentService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AppointmentService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            AppointmentService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AppointmentService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            AppointmentService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AppointmentService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_appointment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AppointmentService::class, 'createAppointment'),
            'AppointmentService must implement createAppointment()'
        );
    }

    public function test_complete_appointment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AppointmentService::class, 'completeAppointment'),
            'AppointmentService must implement completeAppointment()'
        );
    }

    public function test_cancel_appointment_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AppointmentService::class, 'cancelAppointment'),
            'AppointmentService must implement cancelAppointment()'
        );
    }
}
