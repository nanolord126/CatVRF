<?php declare(strict_types=1);

namespace Tests\Unit\Domains\GeoLogistics;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GeoLogisticsService.
 *
 * @covers \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsService
 */
final class GeoLogisticsServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GeoLogisticsService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GeoLogisticsService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GeoLogisticsService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_calculateRoute_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GeoLogistics\Domain\Services\GeoLogisticsService::class, 'calculateRoute'),
            'GeoLogisticsService must implement calculateRoute()'
        );
    }

}
