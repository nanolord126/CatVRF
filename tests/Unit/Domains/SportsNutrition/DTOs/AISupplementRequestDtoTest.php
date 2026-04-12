<?php declare(strict_types=1);

namespace Tests\Unit\Domains\SportsNutrition\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AISupplementRequestDto.
 *
 * @covers \App\Domains\SportsNutrition\DTOs\AISupplementRequestDto
 */
final class AISupplementRequestDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\DTOs\AISupplementRequestDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'AISupplementRequestDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'AISupplementRequestDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\SportsNutrition\DTOs\AISupplementRequestDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('user_id', $params, 'Constructor must have user_id');
        $this->assertContains('goal', $params, 'Constructor must have goal');
        $this->assertContains('weight_kg', $params, 'Constructor must have weight_kg');
        $this->assertContains('age', $params, 'Constructor must have age');
        $this->assertContains('dietary_restriction', $params, 'Constructor must have dietary_restriction');
        $this->assertContains('active_training_days', $params, 'Constructor must have active_training_days');
        $this->assertContains('budget_kopecks_max', $params, 'Constructor must have budget_kopecks_max');
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
        return \App\Domains\SportsNutrition\DTOs\AISupplementRequestDto::class;
    }
}
