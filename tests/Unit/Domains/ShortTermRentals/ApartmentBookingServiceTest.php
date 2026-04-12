<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ShortTermRentals;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ApartmentBookingService.
 *
 * @covers \App\Domains\ShortTermRentals\Domain\Services\ApartmentBookingService
 */
final class ApartmentBookingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Domain\Services\ApartmentBookingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ApartmentBookingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Domain\Services\ApartmentBookingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ApartmentBookingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ShortTermRentals\Domain\Services\ApartmentBookingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ApartmentBookingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_book_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ShortTermRentals\Domain\Services\ApartmentBookingService::class, 'book'),
            'ApartmentBookingService must implement book()'
        );
    }

    public function test_checkout_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ShortTermRentals\Domain\Services\ApartmentBookingService::class, 'checkout'),
            'ApartmentBookingService must implement checkout()'
        );
    }

}
