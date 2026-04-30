<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Art\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Art\Models\Artist;

/**
 * Unit tests for Artist model.
 *
 * @covers \App\Domains\Art\Models\Artist
 */
final class ArtistTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Artist::class
        );
        $this->assertTrue($reflection->isFinal(), 'Artist must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Artist();
        $this->assertNotEmpty($model->getFillable(), 'Artist must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new Artist();
        $this->assertNotEmpty($model->getCasts(), 'Artist must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Artist();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
