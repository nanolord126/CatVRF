<?php declare(strict_types=1);

namespace Tests\Unit\Domains\HobbyAndCraft\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CraftItem model.
 *
 * @covers \App\Domains\HobbyAndCraft\Models\CraftItem
 */
final class CraftItemTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\HobbyAndCraft\Models\CraftItem::class
        );
        $this->assertTrue($reflection->isFinal(), 'CraftItem must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\HobbyAndCraft\Models\CraftItem();
        $this->assertNotEmpty($model->getFillable(), 'CraftItem must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\HobbyAndCraft\Models\CraftItem();
        $this->assertNotEmpty($model->getCasts(), 'CraftItem must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\HobbyAndCraft\Models\CraftItem();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
