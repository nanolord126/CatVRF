<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Restaurant\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateRestaurantDto.
 *
 * @covers \App\Domains\Restaurant\DTOs\CreateRestaurantDto
 */
final class CreateRestaurantDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Restaurant\DTOs\CreateRestaurantDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateRestaurantDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateRestaurantDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Restaurant\DTOs\CreateRestaurantDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('address', $params, 'Constructor must have address');
        $this->assertContains('category', $params, 'Constructor must have category');
        $this->assertContains('cuisineType', $params, 'Constructor must have cuisineType');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
    }

    public function test_has_toArray_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Restaurant\DTOs\CreateRestaurantDto::class, 'toArray'),
            'DTO must implement toArray()'
        );
    }
}
