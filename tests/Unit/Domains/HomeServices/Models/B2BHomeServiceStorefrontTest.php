<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HomeServices\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for B2BHomeServiceStorefront model.
 *
 * @covers \App\Domains\HomeServices\Models\B2BHomeServiceStorefront
 */
final class B2BHomeServiceStorefrontTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HomeServices\Models\B2BHomeServiceStorefront::class
        );
        $this->assertTrue($reflection->isFinal(), 'B2BHomeServiceStorefront must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\HomeServices\Models\B2BHomeServiceStorefront();
        $this->assertNotEmpty($model->getFillable(), 'B2BHomeServiceStorefront must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\HomeServices\Models\B2BHomeServiceStorefront();
        $this->assertNotEmpty($model->getCasts(), 'B2BHomeServiceStorefront must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\HomeServices\Models\B2BHomeServiceStorefront();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
