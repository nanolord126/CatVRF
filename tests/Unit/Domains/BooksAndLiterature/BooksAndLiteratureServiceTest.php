<?php declare(strict_types=1);

namespace Tests\Unit\Domains\BooksAndLiterature;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BooksAndLiteratureService.
 *
 * @covers \App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService
 */
final class BooksAndLiteratureServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BooksAndLiteratureService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'BooksAndLiteratureService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'BooksAndLiteratureService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class, 'create'),
            'BooksAndLiteratureService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class, 'update'),
            'BooksAndLiteratureService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class, 'delete'),
            'BooksAndLiteratureService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class, 'list'),
            'BooksAndLiteratureService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\BooksAndLiterature\Services\BooksAndLiteratureService::class, 'getById'),
            'BooksAndLiteratureService must implement getById()'
        );
    }

}
