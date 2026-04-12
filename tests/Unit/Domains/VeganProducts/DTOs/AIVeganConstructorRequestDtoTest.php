<?php declare(strict_types=1);

namespace Tests\Unit\Domains\VeganProducts\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AIVeganConstructorRequestDto.
 *
 * @covers \App\Domains\VeganProducts\DTOs\AIVeganConstructorRequestDto
 */
final class AIVeganConstructorRequestDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\DTOs\AIVeganConstructorRequestDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'AIVeganConstructorRequestDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AIVeganConstructorRequestDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\VeganProducts\DTOs\AIVeganConstructorRequestDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('userId', $params, 'Constructor must have userId');
        $this->assertContains('dietGoal', $params, 'Constructor must have dietGoal');
        $this->assertContains('allergies', $params, 'Constructor must have allergies');
        $this->assertContains('budgetLimitCop', $params, 'Constructor must have budgetLimitCop');
        $this->assertContains('servingsPerDay', $params, 'Constructor must have servingsPerDay');
        $this->assertContains('favorites', $params, 'Constructor must have favorites');
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
        return \App\Domains\VeganProducts\DTOs\AIVeganConstructorRequestDto::class;
    }
}
