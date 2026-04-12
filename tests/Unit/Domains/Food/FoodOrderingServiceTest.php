<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Food;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FoodOrderingService.
 *
 * @covers \App\Domains\Food\Domain\Services\FoodOrderingService
 */
final class FoodOrderingServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Domain\Services\FoodOrderingService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FoodOrderingService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Domain\Services\FoodOrderingService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FoodOrderingService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Domain\Services\FoodOrderingService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FoodOrderingService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_placeOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Food\Domain\Services\FoodOrderingService::class, 'placeOrder'),
            'FoodOrderingService must implement placeOrder()'
        );
    }

}
