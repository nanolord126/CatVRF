<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Staff;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for StaffService.
 *
 * @covers \App\Domains\Staff\Domain\Services\StaffService
 */
final class StaffServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Staff\Domain\Services\StaffService::class
        );
        $this->assertTrue($reflection->isFinal(), 'StaffService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Staff\Domain\Services\StaffService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'StaffService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Staff\Domain\Services\StaffService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'StaffService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Staff\Domain\Services\StaffService::class, 'create'),
            'StaffService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Staff\Domain\Services\StaffService::class, 'update'),
            'StaffService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Staff\Domain\Services\StaffService::class, 'delete'),
            'StaffService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Staff\Domain\Services\StaffService::class, 'list'),
            'StaffService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Staff\Domain\Services\StaffService::class, 'getById'),
            'StaffService must implement getById()'
        );
    }

}
