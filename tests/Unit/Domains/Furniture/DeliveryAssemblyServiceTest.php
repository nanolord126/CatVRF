<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Furniture;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DeliveryAssemblyService.
 *
 * @covers \App\Domains\Furniture\Domain\Services\DeliveryAssemblyService
 */
final class DeliveryAssemblyServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Domain\Services\DeliveryAssemblyService::class
        );
        $this->assertTrue($reflection->isFinal(), 'DeliveryAssemblyService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Domain\Services\DeliveryAssemblyService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'DeliveryAssemblyService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\Domain\Services\DeliveryAssemblyService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'DeliveryAssemblyService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_createFurnitureOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Furniture\Domain\Services\DeliveryAssemblyService::class, 'createFurnitureOrder'),
            'DeliveryAssemblyService must implement createFurnitureOrder()'
        );
    }

    public function test_startAssembly_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Furniture\Domain\Services\DeliveryAssemblyService::class, 'startAssembly'),
            'DeliveryAssemblyService must implement startAssembly()'
        );
    }

    public function test_completeOrder_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Furniture\Domain\Services\DeliveryAssemblyService::class, 'completeOrder'),
            'DeliveryAssemblyService must implement completeOrder()'
        );
    }

}
