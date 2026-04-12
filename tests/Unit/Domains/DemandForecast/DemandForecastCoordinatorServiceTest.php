<?php declare(strict_types=1);

namespace Tests\Unit\Domains\DemandForecast;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemandForecastCoordinatorService.
 *
 * @covers \App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService
 */
final class DemandForecastCoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'DemandForecastCoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'DemandForecastCoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'DemandForecastCoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class, 'create'),
            'DemandForecastCoordinatorService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class, 'update'),
            'DemandForecastCoordinatorService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class, 'delete'),
            'DemandForecastCoordinatorService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class, 'list'),
            'DemandForecastCoordinatorService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\DemandForecast\Domain\Services\DemandForecastCoordinatorService::class, 'getById'),
            'DemandForecastCoordinatorService must implement getById()'
        );
    }

}
