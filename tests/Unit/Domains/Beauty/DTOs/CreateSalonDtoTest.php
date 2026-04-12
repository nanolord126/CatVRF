<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateSalonDto.
 *
 * @covers \App\Domains\Beauty\DTOs\CreateSalonDto
 */
final class CreateSalonDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Beauty\DTOs\CreateSalonDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateSalonDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateSalonDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Beauty\DTOs\CreateSalonDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('address', $params, 'Constructor must have address');
        $this->assertContains('lat', $params, 'Constructor must have lat');
        $this->assertContains('lon', $params, 'Constructor must have lon');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
        $this->assertContains('tags', $params, 'Constructor must have tags');
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
        return \App\Domains\Beauty\DTOs\CreateSalonDto::class;
    }
}
