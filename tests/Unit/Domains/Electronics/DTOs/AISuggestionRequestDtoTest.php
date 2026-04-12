<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Electronics\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AISuggestionRequestDto.
 *
 * @covers \App\Domains\Electronics\DTOs\AISuggestionRequestDto
 */
final class AISuggestionRequestDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\DTOs\AISuggestionRequestDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'AISuggestionRequestDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AISuggestionRequestDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Electronics\DTOs\AISuggestionRequestDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('categorySlug', $params, 'Constructor must have categorySlug');
        $this->assertContains('budgetMaxKopecks', $params, 'Constructor must have budgetMaxKopecks');
        $this->assertContains('preferredBrands', $params, 'Constructor must have preferredBrands');
        $this->assertContains('interests', $params, 'Constructor must have interests');
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
        return \App\Domains\Electronics\DTOs\AISuggestionRequestDto::class;
    }
}
