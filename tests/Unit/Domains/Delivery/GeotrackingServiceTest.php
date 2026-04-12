<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Delivery;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Delivery\Domain\Services\GeotrackingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GeotrackingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Delivery\Domain\Services\GeotrackingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GeotrackingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Delivery\Domain\Services\GeotrackingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GeotrackingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_updateCourierLocation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\GeotrackingService::class, 'updateCourierLocation'),
            'GeotrackingService must implement updateCourierLocation()'
        );
    }

    public function test_getLiveTrack_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\GeotrackingService::class, 'getLiveTrack'),
            'GeotrackingService must implement getLiveTrack()'
        );
    }

    public function test_startTracking_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\GeotrackingService::class, 'startTracking'),
            'GeotrackingService must implement startTracking()'
        );
    }

    public function test_getCurrentLocation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\GeotrackingService::class, 'getCurrentLocation'),
            'GeotrackingService must implement getCurrentLocation()'
        );
    }

    public function test_setOnlineStatus_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Delivery\Domain\Services\GeotrackingService::class, 'setOnlineStatus'),
            'GeotrackingService must implement setOnlineStatus()'
        );
    }

}
