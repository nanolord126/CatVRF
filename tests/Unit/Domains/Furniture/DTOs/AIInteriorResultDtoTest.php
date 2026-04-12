<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Furniture\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIInteriorResultDto.
 *
 * @covers \App\Domains\Furniture\DTOs\AIInteriorResultDto
 */
final class AIInteriorResultDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\DTOs\AIInteriorResultDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIInteriorResultDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AIInteriorResultDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Furniture\DTOs\AIInteriorResultDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('recommendedProductIds', $params, 'Constructor must have recommendedProductIds');
        $this->assertContains('estimatedTotal', $params, 'Constructor must have estimatedTotal');
        $this->assertContains('layoutStrategy', $params, 'Constructor must have layoutStrategy');
        $this->assertContains('styleAnalysis', $params, 'Constructor must have styleAnalysis');
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
        return \App\Domains\Furniture\DTOs\AIInteriorResultDto::class;
    }
}
