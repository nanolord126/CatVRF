<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Food;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FoodService.
 *
 * @covers \App\Domains\Food\Domain\Services\FoodService
 */
final class FoodServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Domain\Services\FoodService::class
        );
        $this->assertTrue($reflection->isFinal(), 'FoodService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Domain\Services\FoodService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'FoodService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Food\Domain\Services\FoodService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'FoodService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Food\Domain\Services\FoodService::class, 'create'),
            'FoodService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Food\Domain\Services\FoodService::class, 'update'),
            'FoodService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Food\Domain\Services\FoodService::class, 'delete'),
            'FoodService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Food\Domain\Services\FoodService::class, 'list'),
            'FoodService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Food\Domain\Services\FoodService::class, 'getById'),
            'FoodService must implement getById()'
        );
    }

}
