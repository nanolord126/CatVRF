<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Driver model.
 *
 * @covers \App\Domains\Taxi\Models\Driver
 */
final class DriverTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Taxi\Models\Driver::class
        );
        $this->assertTrue($reflection->isFinal(), 'Driver must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Taxi\Models\Driver();
        $this->assertNotEmpty($model->getFillable(), 'Driver must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Taxi\Models\Driver();
        $this->assertNotEmpty($model->getCasts(), 'Driver must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Taxi\Models\Driver();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
