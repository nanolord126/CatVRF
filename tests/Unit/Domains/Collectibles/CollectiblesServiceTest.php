<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Collectibles;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CollectiblesService.
 *
 * @covers \App\Domains\Collectibles\Domain\Services\CollectiblesService
 */
final class CollectiblesServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Collectibles\Domain\Services\CollectiblesService::class
        );
        $this->assertTrue($reflection->isFinal(), 'CollectiblesService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Collectibles\Domain\Services\CollectiblesService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'CollectiblesService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Collectibles\Domain\Services\CollectiblesService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'CollectiblesService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Collectibles\Domain\Services\CollectiblesService::class, 'create'),
            'CollectiblesService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Collectibles\Domain\Services\CollectiblesService::class, 'update'),
            'CollectiblesService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Collectibles\Domain\Services\CollectiblesService::class, 'delete'),
            'CollectiblesService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Collectibles\Domain\Services\CollectiblesService::class, 'list'),
            'CollectiblesService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Collectibles\Domain\Services\CollectiblesService::class, 'getById'),
            'CollectiblesService must implement getById()'
        );
    }

}
