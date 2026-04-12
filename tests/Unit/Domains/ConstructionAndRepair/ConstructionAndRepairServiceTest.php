<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ConstructionAndRepair;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConstructionAndRepairService.
 *
 * @covers \App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService
 */
final class ConstructionAndRepairServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConstructionAndRepairService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ConstructionAndRepairService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ConstructionAndRepairService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class, 'create'),
            'ConstructionAndRepairService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class, 'update'),
            'ConstructionAndRepairService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class, 'delete'),
            'ConstructionAndRepairService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class, 'list'),
            'ConstructionAndRepairService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ConstructionAndRepair\Domain\Services\ConstructionAndRepairService::class, 'getById'),
            'ConstructionAndRepairService must implement getById()'
        );
    }

}
