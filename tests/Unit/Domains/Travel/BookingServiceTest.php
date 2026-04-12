<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Travel;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BookingService.
 *
 * @covers \App\Domains\Travel\Domain\Services\BookingService
 */
final class BookingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Travel\Domain\Services\BookingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BookingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Travel\Domain\Services\BookingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BookingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Travel\Domain\Services\BookingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BookingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Travel\Domain\Services\BookingService::class, 'createBooking'),
            'BookingService must implement createBooking()'
        );
    }

    public function test_payBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Travel\Domain\Services\BookingService::class, 'payBooking'),
            'BookingService must implement payBooking()'
        );
    }

    public function test_cancelBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Travel\Domain\Services\BookingService::class, 'cancelBooking'),
            'BookingService must implement cancelBooking()'
        );
    }

}
