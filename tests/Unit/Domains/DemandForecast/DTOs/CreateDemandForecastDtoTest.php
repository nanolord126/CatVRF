<?php declare(strict_types=1);

namespace Tests\Unit\Domains\DemandForecast\DTOs;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CreateDemandForecastDto.
 *
 * @covers \App\Domains\DemandForecast\DTOs\CreateDemandForecastDto
 */
final class CreateDemandForecastDtoTest extends TestCase
{
    public function test_class_is_final_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\DTOs\CreateDemandForecastDto::class
        );
        $this->assertTrue($reflection->isFinal(), 'CreateDemandForecastDto must be final');
        $this->assertTrue($reflection->isReadOnly(), 'CreateDemandForecastDto must be readonly');
    }

    public function test_constructor_properties(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\DTOs\CreateDemandForecastDto::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor);
        $params = array_map(fn($p) => $p->getName(), $constructor->getParameters());
        $this->assertContains('tenantId', $params, 'Constructor must have tenantId');
        $this->assertContains('businessGroupId', $params, 'Constructor must have businessGroupId');
        $this->assertContains('name', $params, 'Constructor must have name');
        $this->assertContains('description', $params, 'Constructor must have description');
        $this->assertContains('status', $params, 'Constructor must have status');
        $this->assertContains('correlationId', $params, 'Constructor must have correlationId');
        $this->assertContains('idempotencyKey', $params, 'Constructor must have idempotencyKey');
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
        return \App\Domains\DemandForecast\DTOs\CreateDemandForecastDto::class;
    }
}
