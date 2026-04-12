<?php declare(strict_types=1);

namespace Tests\Unit\Domains\DemandForecast;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemandForecastService.
 *
 * @covers \App\Domains\DemandForecast\Domain\Services\DemandForecastService
 */
final class DemandForecastServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Domain\Services\DemandForecastService::class
        );
        $this->assertTrue($reflection->isFinal(), 'DemandForecastService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Domain\Services\DemandForecastService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'DemandForecastService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Domain\Services\DemandForecastService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'DemandForecastService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_forecastForItem_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastService::class, 'forecastForItem'),
            'DemandForecastService must implement forecastForItem()'
        );
    }

    public function test_invalidateCache_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastService::class, 'invalidateCache'),
            'DemandForecastService must implement invalidateCache()'
        );
    }

}
