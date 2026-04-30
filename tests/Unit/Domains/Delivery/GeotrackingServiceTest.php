<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Delivery;

use PHPUnit\Framework\TestCase;
use App\Domains\Delivery\Domain\Services\GeotrackingService;

/**
 * Unit tests for GeotrackingService.
 *
 * @covers \App\Domains\Delivery\Domain\Services\GeotrackingService
 */
final class GeotrackingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            GeotrackingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GeotrackingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            GeotrackingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GeotrackingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            GeotrackingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GeotrackingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_update_courier_location_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GeotrackingService::class, 'updateCourierLocation'),
            'GeotrackingService must implement updateCourierLocation()'
        );
    }

    public function test_get_live_track_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GeotrackingService::class, 'getLiveTrack'),
            'GeotrackingService must implement getLiveTrack()'
        );
    }

    public function test_start_tracking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GeotrackingService::class, 'startTracking'),
            'GeotrackingService must implement startTracking()'
        );
    }

    public function test_get_current_location_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GeotrackingService::class, 'getCurrentLocation'),
            'GeotrackingService must implement getCurrentLocation()'
        );
    }

    public function test_set_online_status_method_exists(): void
    {
        $this->assertTrue(
            method_exists(GeotrackingService::class, 'setOnlineStatus'),
            'GeotrackingService must implement setOnlineStatus()'
        );
    }
}
