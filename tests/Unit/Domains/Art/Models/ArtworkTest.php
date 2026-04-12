<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Art\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Artwork model.
 *
 * @covers \App\Domains\Art\Models\Artwork
 */
final class ArtworkTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Art\Models\Artwork::class
        );
        $this->assertTrue($reflection->isFinal(), 'Artwork must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Art\Models\Artwork();
        $this->assertNotEmpty($model->getFillable(), 'Artwork must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Art\Models\Artwork();
        $this->assertNotEmpty($model->getCasts(), 'Artwork must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Art\Models\Artwork();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
