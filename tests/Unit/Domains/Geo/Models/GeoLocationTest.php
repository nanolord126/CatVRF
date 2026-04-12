<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Geo\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for GeoLocation model.
 *
 * @covers \App\Domains\Geo\Models\GeoLocation
 */
final class GeoLocationTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Geo\Models\GeoLocation::class
        );
        $this->assertTrue($reflection->isFinal(), 'GeoLocation must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Geo\Models\GeoLocation();
        $this->assertNotEmpty($model->getFillable(), 'GeoLocation must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Geo\Models\GeoLocation();
        $this->assertNotEmpty($model->getCasts(), 'GeoLocation must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Geo\Models\GeoLocation();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
