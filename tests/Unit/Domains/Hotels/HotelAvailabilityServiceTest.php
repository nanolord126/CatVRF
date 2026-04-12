<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HotelAvailabilityService.
 *
 * @covers \App\Domains\Hotels\Domain\Services\HotelAvailabilityService
 */
final class HotelAvailabilityServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\Domain\Services\HotelAvailabilityService::class
        );
        $this->assertTrue($reflection->isFinal(), 'HotelAvailabilityService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\Domain\Services\HotelAvailabilityService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'HotelAvailabilityService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\Domain\Services\HotelAvailabilityService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'HotelAvailabilityService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_isAvailable_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Hotels\Domain\Services\HotelAvailabilityService::class, 'isAvailable'),
            'HotelAvailabilityService must implement isAvailable()'
        );
    }

    public function test_getAvailableRooms_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Hotels\Domain\Services\HotelAvailabilityService::class, 'getAvailableRooms'),
            'HotelAvailabilityService must implement getAvailableRooms()'
        );
    }

    public function test_syncRoomStock_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Hotels\Domain\Services\HotelAvailabilityService::class, 'syncRoomStock'),
            'HotelAvailabilityService must implement syncRoomStock()'
        );
    }

}
