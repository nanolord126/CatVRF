<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Logistics;

use PHPUnit\Framework\TestCase;

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
            \App\Domains\Logistics\Domain\Services\CourierService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CourierService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Domain\Services\CourierService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CourierService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Domain\Services\CourierService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CourierService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_registerCourier_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierService::class, 'registerCourier'),
            'CourierService must implement registerCourier()'
        );
    }

    public function test_goOnline_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierService::class, 'goOnline'),
            'CourierService must implement goOnline()'
        );
    }

    public function test_goOffline_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierService::class, 'goOffline'),
            'CourierService must implement goOffline()'
        );
    }

    public function test_updateLocation_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierService::class, 'updateLocation'),
            'CourierService must implement updateLocation()'
        );
    }

    public function test_updateRating_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierService::class, 'updateRating'),
            'CourierService must implement updateRating()'
        );
    }

}
