<?php declare(strict_types=1);

namespace Tests\Unit\Domains\AI;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AICoordinatorService.
 *
 * @covers \App\Domains\AI\Domain\Services\AICoordinatorService
 */
final class AICoordinatorServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AICoordinatorService::class
        );
        $this->assertTrue($reflection->isFinal(), 'AICoordinatorService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AICoordinatorService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'AICoordinatorService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\AI\Domain\Services\AICoordinatorService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'AICoordinatorService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AICoordinatorService::class, 'create'),
            'AICoordinatorService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AICoordinatorService::class, 'update'),
            'AICoordinatorService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AICoordinatorService::class, 'delete'),
            'AICoordinatorService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AICoordinatorService::class, 'list'),
            'AICoordinatorService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\AI\Domain\Services\AICoordinatorService::class, 'getById'),
            'AICoordinatorService must implement getById()'
        );
    }

}
