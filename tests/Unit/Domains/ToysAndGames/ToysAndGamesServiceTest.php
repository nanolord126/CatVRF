<?php declare(strict_types=1);

namespace Tests\Unit\Domains\ToysAndGames;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ToysAndGamesService.
 *
 * @covers \App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService
 */
final class ToysAndGamesServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ToysAndGamesService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ToysAndGamesService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ToysAndGamesService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class, 'create'),
            'ToysAndGamesService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class, 'update'),
            'ToysAndGamesService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class, 'delete'),
            'ToysAndGamesService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class, 'list'),
            'ToysAndGamesService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\ToysAndGames\Domain\Services\ToysAndGamesService::class, 'getById'),
            'ToysAndGamesService must implement getById()'
        );
    }

}
