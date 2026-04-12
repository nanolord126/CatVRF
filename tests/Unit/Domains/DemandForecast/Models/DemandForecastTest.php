<?php declare(strict_types=1);

namespace Tests\Unit\Domains\DemandForecast\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemandForecast model.
 *
 * @covers \App\Domains\DemandForecast\Models\DemandForecast
 */
final class DemandForecastTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\DemandForecast\Models\DemandForecast::class
        );
        $this->assertTrue($reflection->isFinal(), 'DemandForecast must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\DemandForecast\Models\DemandForecast();
        $this->assertNotEmpty($model->getFillable(), 'DemandForecast must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\DemandForecast\Models\DemandForecast();
        $this->assertNotEmpty($model->getCasts(), 'DemandForecast must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\DemandForecast\Models\DemandForecast();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
