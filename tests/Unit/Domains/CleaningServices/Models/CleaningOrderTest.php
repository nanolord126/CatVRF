<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CleaningServices\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CleaningOrder model.
 *
 * @covers \App\Domains\CleaningServices\Models\CleaningOrder
 */
final class CleaningOrderTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CleaningServices\Models\CleaningOrder::class
        );
        $this->assertTrue($reflection->isFinal(), 'CleaningOrder must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\CleaningServices\Models\CleaningOrder();
        $this->assertNotEmpty($model->getFillable(), 'CleaningOrder must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\CleaningServices\Models\CleaningOrder();
        $this->assertNotEmpty($model->getCasts(), 'CleaningOrder must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\CleaningServices\Models\CleaningOrder();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
