<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Logistics;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CourierServiceService.
 *
 * @covers \App\Domains\Logistics\Domain\Services\CourierServiceService
 */
final class CourierServiceServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Domain\Services\CourierServiceService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CourierServiceService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Domain\Services\CourierServiceService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CourierServiceService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Logistics\Domain\Services\CourierServiceService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CourierServiceService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createCourierService_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierServiceService::class, 'createCourierService'),
            'CourierServiceService must implement createCourierService()'
        );
    }

    public function test_updateCourierService_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Logistics\Domain\Services\CourierServiceService::class, 'updateCourierService'),
            'CourierServiceService must implement updateCourierService()'
        );
    }

}
