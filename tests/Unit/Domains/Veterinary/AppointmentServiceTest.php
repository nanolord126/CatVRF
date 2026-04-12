<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Veterinary;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AppointmentService.
 *
 * @covers \App\Domains\Veterinary\Domain\Services\AppointmentService
 */
final class AppointmentServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Veterinary\Domain\Services\AppointmentService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AppointmentService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Veterinary\Domain\Services\AppointmentService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AppointmentService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Veterinary\Domain\Services\AppointmentService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AppointmentService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Veterinary\Domain\Services\AppointmentService::class, 'create'),
            'AppointmentService must implement create()'
        );
    }

    public function test_cancel_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Veterinary\Domain\Services\AppointmentService::class, 'cancel'),
            'AppointmentService must implement cancel()'
        );
    }

    public function test_complete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Veterinary\Domain\Services\AppointmentService::class, 'complete'),
            'AppointmentService must implement complete()'
        );
    }

}
