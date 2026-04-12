<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Communication\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateChannelDto.
 *
 * @covers \App\Domains\Communication\DTOs\CreateChannelDto
 */
final class CreateChannelDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\DTOs\CreateChannelDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateChannelDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateChannelDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Communication\DTOs\CreateChannelDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('type', $params, 'Constructor must have type');
        $this->assertContains('config', $params, 'Constructor must have config');
        $this->assertContains('status', $params, 'Constructor must have status');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
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
        return \App\Domains\Communication\DTOs\CreateChannelDto::class;
    }
}
