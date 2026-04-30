<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Common\DTOs;

use PHPUnit\Framework\TestCase;
use App\Domains\Common\DTOs\UpdateCommonEntityDto;

/**
 * Unit tests for UpdateCommonEntityDto.
 *
 * @covers \App\Domains\Common\DTOs\UpdateCommonEntityDto
 */
final class UpdateCommonEntityDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            UpdateCommonEntityDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'UpdateCommonEntityDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'UpdateCommonEntityDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            UpdateCommonEntityDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn ($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('description', $params, 'Constructor must have description');
        $this->assertContains('status', $params, 'Constructor must have status');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
    }

    public function test_has_to_array_method(): void
    {
        $this->assertTrue(
            method_exists($this->getDtoClass(), 'toArray'),
            'DTO must implement toArray()'
        );
    }

    private function getDtoClass(): string
    {
        return UpdateCommonEntityDto::class;
    }
}
