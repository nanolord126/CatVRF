<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ElectronicsService.
 *
 * @covers \App\Domains\Electronics\Domain\Services\ElectronicsService
 */
final class ElectronicsServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\Domain\Services\ElectronicsService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ElectronicsService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\Domain\Services\ElectronicsService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ElectronicsService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\Domain\Services\ElectronicsService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ElectronicsService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Electronics\Domain\Services\ElectronicsService::class, 'createOrder'),
            'ElectronicsService must implement createOrder()'
        );
    }

    public function test_completeOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Electronics\Domain\Services\ElectronicsService::class, 'completeOrder'),
            'ElectronicsService must implement completeOrder()'
        );
    }

    public function test_cancelOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Electronics\Domain\Services\ElectronicsService::class, 'cancelOrder'),
            'ElectronicsService must implement cancelOrder()'
        );
    }

    public function test_getOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Electronics\Domain\Services\ElectronicsService::class, 'getOrder'),
            'ElectronicsService must implement getOrder()'
        );
    }

    public function test_getUserOrders_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Electronics\Domain\Services\ElectronicsService::class, 'getUserOrders'),
            'ElectronicsService must implement getUserOrders()'
        );
    }

}
