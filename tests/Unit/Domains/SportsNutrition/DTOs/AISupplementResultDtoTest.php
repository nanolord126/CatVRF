<?php declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AISupplementResultDto.
 *
 * @covers \App\Domains\SportsNutrition\DTOs\AISupplementResultDto
 */
final class AISupplementResultDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\DTOs\AISupplementResultDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'AISupplementResultDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AISupplementResultDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\DTOs\AISupplementResultDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('vertical', $params, 'Constructor must have vertical');
        $this->assertContains('recommended_stack_name', $params, 'Constructor must have recommended_stack_name');
        $this->assertContains('payload', $params, 'Constructor must have payload');
        $this->assertContains('confidence_score', $params, 'Constructor must have confidence_score');
        $this->assertContains('correlation_id', $params, 'Constructor must have correlation_id');
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
        return \App\Domains\SportsNutrition\DTOs\AISupplementResultDto::class;
    }
}
