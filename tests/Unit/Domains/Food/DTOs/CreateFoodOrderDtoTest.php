<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Food\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateFoodOrderDto.
 *
 * @covers \App\Domains\Food\DTOs\CreateFoodOrderDto
 */
final class CreateFoodOrderDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\DTOs\CreateFoodOrderDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateFoodOrderDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateFoodOrderDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\DTOs\CreateFoodOrderDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('restaurantId', $params, 'Constructor must have restaurantId');
        $this->assertContains('customerId', $params, 'Constructor must have customerId');
        $this->assertContains('items', $params, 'Constructor must have items');
        $this->assertContains('deliveryLat', $params, 'Constructor must have deliveryLat');
        $this->assertContains('deliveryLon', $params, 'Constructor must have deliveryLon');
        $this->assertContains('deliveryAddress', $params, 'Constructor must have deliveryAddress');
        $this->assertContains('specialInstructions', $params, 'Constructor must have specialInstructions');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
    }

    public function test_has_toArray_method(): void
    {
        $this->assertTrue(
            method_exists($this->getDtoClass(), 'toArray'),
            'DTO must implement toArray()'
        );
    }

    private function getDtoClass(): string
    {
        return \App\Domains\Food\DTOs\CreateFoodOrderDto::class;
    }
}
