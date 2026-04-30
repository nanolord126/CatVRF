<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels;

use PHPUnit\Framework\TestCase;
use App\Domains\Hotels\Domain\Services\HotelAvailabilityService;

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
            HotelAvailabilityService::class
        );
        $this->assertTrue($reflection->isFinal(), 'HotelAvailabilityService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            HotelAvailabilityService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'HotelAvailabilityService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            HotelAvailabilityService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'HotelAvailabilityService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_is_available_method_exists(): void
    {
        $this->assertTrue(
            method_exists(HotelAvailabilityService::class, 'isAvailable'),
            'HotelAvailabilityService must implement isAvailable()'
        );
    }

    public function test_get_available_rooms_method_exists(): void
    {
        $this->assertTrue(
            method_exists(HotelAvailabilityService::class, 'getAvailableRooms'),
            'HotelAvailabilityService must implement getAvailableRooms()'
        );
    }

    public function test_sync_room_stock_method_exists(): void
    {
        $this->assertTrue(
            method_exists(HotelAvailabilityService::class, 'syncRoomStock'),
            'HotelAvailabilityService must implement syncRoomStock()'
        );
    }
}
