<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Hotels\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Amenity model.
 *
 * @covers \App\Domains\Hotels\Models\Amenity
 */
final class AmenityTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Hotels\Models\Amenity::class
        );
        $this->assertTrue($reflection->isFinal(), 'Amenity must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Hotels\Models\Amenity();
        $this->assertNotEmpty($model->getFillable(), 'Amenity must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Hotels\Models\Amenity();
        $this->assertNotEmpty($model->getCasts(), 'Amenity must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Hotels\Models\Amenity();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
