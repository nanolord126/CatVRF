<?php declare(strict_types=1);

namespace Tests\Unit\Domains\DemandForecast\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UpdateDemandForecastDto.
 *
 * @covers \App\Domains\DemandForecast\DTOs\UpdateDemandForecastDto
 */
final class UpdateDemandForecastDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\DTOs\UpdateDemandForecastDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'UpdateDemandForecastDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'UpdateDemandForecastDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\DTOs\UpdateDemandForecastDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('description', $params, 'Constructor must have description');
        $this->assertContains('status', $params, 'Constructor must have status');
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
        return \App\Domains\DemandForecast\DTOs\UpdateDemandForecastDto::class;
    }
}
