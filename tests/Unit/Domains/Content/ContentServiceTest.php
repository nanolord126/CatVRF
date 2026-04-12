<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Content;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ContentService.
 *
 * @covers \App\Domains\Content\Services\ContentService
 */
final class ContentServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Content\Services\ContentService::class
        );
        $this->assertTrue($reflection->isFinal(), 'ContentService must be final');
    }

    public function test_class_is_readonly(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Content\Services\ContentService::class
        );
        $this->assertTrue($reflection->isReadOnly(), 'ContentService must be readonly');
    }

    public function test_has_constructor_injection(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Content\Services\ContentService::class
        );
        $constructor = $reflection->getConstructor();
        $this->assertNotNull($constructor, 'ContentService must have __construct');
        $this->assertGreaterThan(0, $constructor->getNumberOfParameters());
    }

    public function test_create_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Content\Services\ContentService::class, 'create'),
            'ContentService must implement create()'
        );
    }

    public function test_update_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Content\Services\ContentService::class, 'update'),
            'ContentService must implement update()'
        );
    }

    public function test_delete_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Content\Services\ContentService::class, 'delete'),
            'ContentService must implement delete()'
        );
    }

    public function test_list_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Content\Services\ContentService::class, 'list'),
            'ContentService must implement list()'
        );
    }

    public function test_getById_method_exists(): void
    {
        $this->assertTrue(
            method_exists(\App\Domains\Content\Services\ContentService::class, 'getById'),
            'ContentService must implement getById()'
        );
    }

}


