<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Logistics;

use PHPUnit\Framework\TestCase;
use App\Domains\Logistics\Domain\Services\CourierService;

/**
 * Unit tests for CourierService.
 *
 * @covers \App\Domains\Logistics\Domain\Services\CourierService
 */
final class CourierServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            CourierService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CourierService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            CourierService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CourierService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            CourierService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CourierService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_register_courier_method_exists(): void
    {
        $this->assertTrue(
            method_exists(CourierService::class, 'registerCourier'),
            'CourierService must implement registerCourier()'
        );
    }

    public function test_go_online_method_exists(): void
    {
        $this->assertTrue(
            method_exists(CourierService::class, 'goOnline'),
            'CourierService must implement goOnline()'
        );
    }

    public function test_go_offline_method_exists(): void
    {
        $this->assertTrue(
            method_exists(CourierService::class, 'goOffline'),
            'CourierService must implement goOffline()'
        );
    }

    public function test_update_location_method_exists(): void
    {
        $this->assertTrue(
            method_exists(CourierService::class, 'updateLocation'),
            'CourierService must implement updateLocation()'
        );
    }

    public function test_update_rating_method_exists(): void
    {
        $this->assertTrue(
            method_exists(CourierService::class, 'updateRating'),
            'CourierService must implement updateRating()'
        );
    }
}
