<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Inventory\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateReservationDto.
 *
 * @covers \App\Domains\Inventory\DTOs\CreateReservationDto
 */
final class CreateReservationDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\DTOs\CreateReservationDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateReservationDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateReservationDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Inventory\DTOs\CreateReservationDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('productId', $params, 'Constructor must have productId');
        $this->assertContains('warehouseId', $params, 'Constructor must have warehouseId');
        $this->assertContains('quantity', $params, 'Constructor must have quantity');
        $this->assertContains('sourceType', $params, 'Constructor must have sourceType');
        $this->assertContains('sourceId', $params, 'Constructor must have sourceId');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('cartId', $params, 'Constructor must have cartId');
        $this->assertContains('orderId', $params, 'Constructor must have orderId');
        $this->assertContains('expiresAt', $params, 'Constructor must have expiresAt');
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
        return \App\Domains\Inventory\DTOs\CreateReservationDto::class;
    }
}
