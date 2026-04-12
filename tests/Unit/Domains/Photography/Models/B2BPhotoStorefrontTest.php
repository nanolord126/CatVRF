<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Photography\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BPhotoStorefront model.
 *
 * @covers \App\Domains\Photography\Models\B2BPhotoStorefront
 */
final class B2BPhotoStorefrontTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Photography\Models\B2BPhotoStorefront::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BPhotoStorefront must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Photography\Models\B2BPhotoStorefront();
        $this->assertNotEmpty($model->getFillable(), 'B2BPhotoStorefront must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Photography\Models\B2BPhotoStorefront();
        $this->assertNotEmpty($model->getCasts(), 'B2BPhotoStorefront must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Photography\Models\B2BPhotoStorefront();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
