<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CarRental;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CarRentalService.
 *
 * @covers \App\Domains\CarRental\Domain\Services\CarRentalService
 */
final class CarRentalServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CarRental\Domain\Services\CarRentalService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CarRentalService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CarRental\Domain\Services\CarRentalService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CarRentalService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CarRental\Domain\Services\CarRentalService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CarRentalService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CarRental\Domain\Services\CarRentalService::class, 'createBooking'),
            'CarRentalService must implement createBooking()'
        );
    }

    public function test_completeBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CarRental\Domain\Services\CarRentalService::class, 'completeBooking'),
            'CarRentalService must implement completeBooking()'
        );
    }

    public function test_cancelBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CarRental\Domain\Services\CarRentalService::class, 'cancelBooking'),
            'CarRentalService must implement cancelBooking()'
        );
    }

    public function test_getBooking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CarRental\Domain\Services\CarRentalService::class, 'getBooking'),
            'CarRentalService must implement getBooking()'
        );
    }

    public function test_getUserBookings_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CarRental\Domain\Services\CarRentalService::class, 'getUserBookings'),
            'CarRentalService must implement getUserBookings()'
        );
    }

}
