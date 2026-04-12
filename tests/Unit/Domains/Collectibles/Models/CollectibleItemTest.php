<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Collectibles\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CollectibleItem model.
 *
 * @covers \App\Domains\Collectibles\Models\CollectibleItem
 */
final class CollectibleItemTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Collectibles\Models\CollectibleItem::class
        );
        $this->assertTrue($reflection->isFinal(), 'CollectibleItem must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Collectibles\Models\CollectibleItem();
        $this->assertNotEmpty($model->getFillable(), 'CollectibleItem must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Collectibles\Models\CollectibleItem();
        $this->assertNotEmpty($model->getCasts(), 'CollectibleItem must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Collectibles\Models\CollectibleItem();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
