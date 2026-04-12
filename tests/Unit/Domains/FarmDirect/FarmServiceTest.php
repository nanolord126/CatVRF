<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FarmDirect;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FarmService.
 *
 * @covers \App\Domains\FarmDirect\Domain\Services\FarmService
 */
final class FarmServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Domain\Services\FarmService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FarmService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Domain\Services\FarmService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FarmService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FarmDirect\Domain\Services\FarmService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FarmService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmService::class, 'createOrder'),
            'FarmService must implement createOrder()'
        );
    }

    public function test_completeOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmService::class, 'completeOrder'),
            'FarmService must implement completeOrder()'
        );
    }

    public function test_cancelOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmService::class, 'cancelOrder'),
            'FarmService must implement cancelOrder()'
        );
    }

    public function test_getOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmService::class, 'getOrder'),
            'FarmService must implement getOrder()'
        );
    }

    public function test_getUserOrders_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\FarmDirect\Domain\Services\FarmService::class, 'getUserOrders'),
            'FarmService must implement getUserOrders()'
        );
    }

}
