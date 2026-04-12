<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Photography;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookingService.
 *
 * @covers \App\Domains\Photography\Domain\Services\BookingService
 */
final class BookingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Domain\Services\BookingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BookingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Domain\Services\BookingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BookingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Domain\Services\BookingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BookingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Photography\Domain\Services\BookingService::class, 'createBooking'),
            'BookingService must implement createBooking()'
        );
    }

    public function test_reschedule_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Photography\Domain\Services\BookingService::class, 'reschedule'),
            'BookingService must implement reschedule()'
        );
    }

    public function test_cancel_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Photography\Domain\Services\BookingService::class, 'cancel'),
            'BookingService must implement cancel()'
        );
    }

}
