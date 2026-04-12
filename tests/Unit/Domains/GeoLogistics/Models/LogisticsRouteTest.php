<?php declare(strict_types=1);

namespace Tests\Unit\Domains\GeoLogistics\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LogisticsRoute model.
 *
 * @covers \App\Domains\GeoLogistics\Models\LogisticsRoute
 */
final class LogisticsRouteTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\GeoLogistics\Models\LogisticsRoute::class
        );
        $this->assertTrue($reflection->isFinal(), 'LogisticsRoute must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\GeoLogistics\Models\LogisticsRoute();
        $this->assertNotEmpty($model->getFillable(), 'LogisticsRoute must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\GeoLogistics\Models\LogisticsRoute();
        $this->assertNotEmpty($model->getCasts(), 'LogisticsRoute must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\GeoLogistics\Models\LogisticsRoute();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
