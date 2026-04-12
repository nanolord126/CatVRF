<?php declare(strict_types=1);

namespace Tests\Unit\Domains\GeoLogistics;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GeoLogisticsCoordinatorService.
 *
 * @covers \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService
 */
final class GeoLogisticsCoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GeoLogisticsCoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GeoLogisticsCoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GeoLogisticsCoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class, 'create'),
            'GeoLogisticsCoordinatorService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class, 'update'),
            'GeoLogisticsCoordinatorService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class, 'delete'),
            'GeoLogisticsCoordinatorService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class, 'list'),
            'GeoLogisticsCoordinatorService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\GeoLogistics\Domain\Services\GeoLogisticsCoordinatorService::class, 'getById'),
            'GeoLogisticsCoordinatorService must implement getById()'
        );
    }

}
