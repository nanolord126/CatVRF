<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auto\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Auto\Models\AutoCatalogBrand;

/**
 * Unit tests for AutoCatalogBrand model.
 *
 * @covers \App\Domains\Auto\Models\AutoCatalogBrand
 */
final class AutoCatalogBrandTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            AutoCatalogBrand::class
        );
        $this->assertTrue($reflection->isFinal(), 'AutoCatalogBrand must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new AutoCatalogBrand();
        $this->assertNotEmpty($model->getFillable(), 'AutoCatalogBrand must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new AutoCatalogBrand();
        $this->assertNotEmpty($model->getCasts(), 'AutoCatalogBrand must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new AutoCatalogBrand();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
