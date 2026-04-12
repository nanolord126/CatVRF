<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Luxury\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LuxuryBrand model.
 *
 * @covers \App\Domains\Luxury\Models\LuxuryBrand
 */
final class LuxuryBrandTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Luxury\Models\LuxuryBrand::class
        );
        $this->assertTrue($reflection->isFinal(), 'LuxuryBrand must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Luxury\Models\LuxuryBrand();
        $this->assertNotEmpty($model->getFillable(), 'LuxuryBrand must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Luxury\Models\LuxuryBrand();
        $this->assertNotEmpty($model->getCasts(), 'LuxuryBrand must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Luxury\Models\LuxuryBrand();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
