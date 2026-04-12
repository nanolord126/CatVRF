<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HobbyAndCraft;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for HobbyAndCraftService.
 *
 * @covers \App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService
 */
final class HobbyAndCraftServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class
        );
        $this->assertTrue($reflection->isFinal(), 'HobbyAndCraftService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'HobbyAndCraftService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'HobbyAndCraftService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class, 'create'),
            'HobbyAndCraftService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class, 'update'),
            'HobbyAndCraftService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class, 'delete'),
            'HobbyAndCraftService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class, 'list'),
            'HobbyAndCraftService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\HobbyAndCraft\Domain\Services\HobbyAndCraftService::class, 'getById'),
            'HobbyAndCraftService must implement getById()'
        );
    }

}
