<?php declare(strict_types=1);

namespace Tests\Unit\Domains\BooksAndLiterature\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Book model.
 *
 * @covers \App\Domains\BooksAndLiterature\Models\Book
 */
final class BookTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\BooksAndLiterature\Models\Book::class
        );
        $this->assertTrue($reflection->isFinal(), 'Book must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\BooksAndLiterature\Models\Book();
        $this->assertNotEmpty($model->getFillable(), 'Book must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\BooksAndLiterature\Models\Book();
        $this->assertNotEmpty($model->getCasts(), 'Book must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\BooksAndLiterature\Models\Book();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
