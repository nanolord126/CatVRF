<?php declare(strict_types=1);

namespace Tests\Unit\Domains\OfficeCatering;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OfficeCateringService.
 *
 * @covers \App\Domains\OfficeCatering\Domain\Services\OfficeCateringService
 */
final class OfficeCateringServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class
        );
        $this->assertTrue($reflection->isFinal(), 'OfficeCateringService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'OfficeCateringService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'OfficeCateringService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class, 'createOrder'),
            'OfficeCateringService must implement createOrder()'
        );
    }

    public function test_completeOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class, 'completeOrder'),
            'OfficeCateringService must implement completeOrder()'
        );
    }

    public function test_cancelOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class, 'cancelOrder'),
            'OfficeCateringService must implement cancelOrder()'
        );
    }

    public function test_getOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class, 'getOrder'),
            'OfficeCateringService must implement getOrder()'
        );
    }

    public function test_getUserOrders_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\OfficeCatering\Domain\Services\OfficeCateringService::class, 'getUserOrders'),
            'OfficeCateringService must implement getUserOrders()'
        );
    }

}
