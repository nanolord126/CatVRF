<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\FarmDirect\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\FarmDirect\Models\FarmOrder;

/**
 * Unit tests for FarmOrder model.
 *
 * @covers \App\Domains\FarmDirect\Models\FarmOrder
 */
final class FarmOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            FarmOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'FarmOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new FarmOrder();
        $this->assertNotEmpty($model->getFillable(), 'FarmOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new FarmOrder();
        $this->assertNotEmpty($model->getCasts(), 'FarmOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new FarmOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
