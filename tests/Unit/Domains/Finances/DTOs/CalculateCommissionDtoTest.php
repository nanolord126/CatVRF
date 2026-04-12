<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CalculateCommissionDto.
 *
 * @covers \App\Domains\Finances\DTOs\CalculateCommissionDto
 */
final class CalculateCommissionDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Finances\DTOs\CalculateCommissionDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CalculateCommissionDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CalculateCommissionDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Finances\DTOs\CalculateCommissionDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('amountKopecks', $params, 'Constructor must have amountKopecks');
        $this->assertContains('isB2B', $params, 'Constructor must have isB2B');
        $this->assertContains('b2bTier', $params, 'Constructor must have b2bTier');
        $this->assertContains('vertical', $params, 'Constructor must have vertical');
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
        return \App\Domains\Finances\DTOs\CalculateCommissionDto::class;
    }
}
