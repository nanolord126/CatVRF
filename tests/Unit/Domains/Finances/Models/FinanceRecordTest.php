<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Finances\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FinanceRecord model.
 *
 * @covers \App\Domains\Finances\Models\FinanceRecord
 */
final class FinanceRecordTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Finances\Models\FinanceRecord::class
        );
        $this->assertTrue($reflection->isFinal(), 'FinanceRecord must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Finances\Models\FinanceRecord();
        $this->assertNotEmpty($model->getFillable(), 'FinanceRecord must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Finances\Models\FinanceRecord();
        $this->assertNotEmpty($model->getCasts(), 'FinanceRecord must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Finances\Models\FinanceRecord();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
