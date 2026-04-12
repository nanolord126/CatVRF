<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CleaningServices;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CleaningServicesService.
 *
 * @covers \App\Domains\CleaningServices\Domain\Services\CleaningServicesService
 */
final class CleaningServicesServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CleaningServicesService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CleaningServicesService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CleaningServicesService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class, 'create'),
            'CleaningServicesService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class, 'update'),
            'CleaningServicesService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class, 'delete'),
            'CleaningServicesService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class, 'list'),
            'CleaningServicesService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\CleaningServices\Domain\Services\CleaningServicesService::class, 'getById'),
            'CleaningServicesService must implement getById()'
        );
    }

}
