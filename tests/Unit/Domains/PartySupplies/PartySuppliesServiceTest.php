<?php declare(strict_types=1);

namespace Tests\Unit\Domains\PartySupplies;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PartySuppliesService.
 *
 * @covers \App\Domains\PartySupplies\Domain\Services\PartySuppliesService
 */
final class PartySuppliesServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class
        );
        $this->assertTrue($reflection->isFinal(), 'PartySuppliesService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'PartySuppliesService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'PartySuppliesService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class, 'createOrder'),
            'PartySuppliesService must implement createOrder()'
        );
    }

    public function test_completeOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class, 'completeOrder'),
            'PartySuppliesService must implement completeOrder()'
        );
    }

    public function test_cancelOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class, 'cancelOrder'),
            'PartySuppliesService must implement cancelOrder()'
        );
    }

    public function test_getOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class, 'getOrder'),
            'PartySuppliesService must implement getOrder()'
        );
    }

    public function test_getUserOrders_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\PartySupplies\Domain\Services\PartySuppliesService::class, 'getUserOrders'),
            'PartySuppliesService must implement getUserOrders()'
        );
    }

}
