<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Collectibles\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateItemDto.
 *
 * @covers \App\Domains\Collectibles\DTOs\CreateItemDto
 */
final class CreateItemDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Collectibles\DTOs\CreateItemDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateItemDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateItemDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Collectibles\DTOs\CreateItemDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('userId', $params, 'Constructor must have userId');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('data', $params, 'Constructor must have data');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
        $this->assertContains('isB2B', $params, 'Constructor must have isB2B');
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
        return \App\Domains\Collectibles\DTOs\CreateItemDto::class;
    }
}
