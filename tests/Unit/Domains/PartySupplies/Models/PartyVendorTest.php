<?php declare(strict_types=1);

namespace Tests\Unit\Domains\PartySupplies\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PartyVendor model.
 *
 * @covers \App\Domains\PartySupplies\Models\PartyVendor
 */
final class PartyVendorTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PartySupplies\Models\PartyVendor::class
        );
        $this->assertTrue($reflection->isFinal(), 'PartyVendor must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\PartySupplies\Models\PartyVendor();
        $this->assertNotEmpty($model->getFillable(), 'PartyVendor must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\PartySupplies\Models\PartyVendor();
        $this->assertNotEmpty($model->getCasts(), 'PartyVendor must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\PartySupplies\Models\PartyVendor();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
