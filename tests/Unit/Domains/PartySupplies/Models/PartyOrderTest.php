<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\PartySupplies\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\PartySupplies\Models\PartyOrder;

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
            PartyOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'PartyOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new PartyOrder();
        $this->assertNotEmpty($model->getFillable(), 'PartyOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new PartyOrder();
        $this->assertNotEmpty($model->getCasts(), 'PartyOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new PartyOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
