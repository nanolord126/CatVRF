<?php declare(strict_types=1);

namespace Tests\Unit\Domains\MeatShops\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MeatConsumable model.
 *
 * @covers \App\Domains\MeatShops\Models\MeatConsumable
 */
final class MeatConsumableTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\MeatShops\Models\MeatConsumable::class
        );
        $this->assertTrue($reflection->isFinal(), 'MeatConsumable must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\MeatShops\Models\MeatConsumable();
        $this->assertNotEmpty($model->getFillable(), 'MeatConsumable must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\MeatShops\Models\MeatConsumable();
        $this->assertNotEmpty($model->getCasts(), 'MeatConsumable must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\MeatShops\Models\MeatConsumable();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
