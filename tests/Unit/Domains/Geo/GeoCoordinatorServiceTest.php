<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Geo;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GeoCoordinatorService.
 *
 * @covers \App\Domains\Geo\Domain\Services\GeoCoordinatorService
 */
final class GeoCoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Domain\Services\GeoCoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'GeoCoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Domain\Services\GeoCoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'GeoCoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Domain\Services\GeoCoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'GeoCoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\GeoCoordinatorService::class, 'create'),
            'GeoCoordinatorService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\GeoCoordinatorService::class, 'update'),
            'GeoCoordinatorService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\GeoCoordinatorService::class, 'delete'),
            'GeoCoordinatorService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\GeoCoordinatorService::class, 'list'),
            'GeoCoordinatorService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Geo\Domain\Services\GeoCoordinatorService::class, 'getById'),
            'GeoCoordinatorService must implement getById()'
        );
    }

}
