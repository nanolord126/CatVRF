<?php declare(strict_types=1);

namespace Tests\Unit\Domains\PartySupplies\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PartyOrder model.
 *
 * @covers \App\Domains\PartySupplies\Models\PartyOrder
 */
final class PartyOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PartySupplies\Models\PartyOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'PartyOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\PartySupplies\Models\PartyOrder();
        $this->assertNotEmpty($model->getFillable(), 'PartyOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\PartySupplies\Models\PartyOrder();
        $this->assertNotEmpty($model->getCasts(), 'PartyOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\PartySupplies\Models\PartyOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
