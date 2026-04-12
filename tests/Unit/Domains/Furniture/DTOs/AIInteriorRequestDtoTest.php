<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Furniture\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIInteriorRequestDto.
 *
 * @covers \App\Domains\Furniture\DTOs\AIInteriorRequestDto
 */
final class AIInteriorRequestDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\DTOs\AIInteriorRequestDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIInteriorRequestDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AIInteriorRequestDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\DTOs\AIInteriorRequestDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('roomTypeId', $params, 'Constructor must have roomTypeId');
        $this->assertContains('stylePreference', $params, 'Constructor must have stylePreference');
        $this->assertContains('budgetKopecks', $params, 'Constructor must have budgetKopecks');
        $this->assertContains('photoPath', $params, 'Constructor must have photoPath');
        $this->assertContains('existingFurnitureIds', $params, 'Constructor must have existingFurnitureIds');
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
        return \App\Domains\Furniture\DTOs\AIInteriorRequestDto::class;
    }
}
