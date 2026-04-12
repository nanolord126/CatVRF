<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PaymentRecord model.
 *
 * @covers \App\Domains\Payment\Models\PaymentRecord
 */
final class PaymentRecordTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Payment\Models\PaymentRecord::class
        );
        $this->assertTrue($reflection->isFinal(), 'PaymentRecord must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Payment\Models\PaymentRecord();
        $this->assertNotEmpty($model->getFillable(), 'PaymentRecord must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Payment\Models\PaymentRecord();
        $this->assertNotEmpty($model->getCasts(), 'PaymentRecord must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Payment\Models\PaymentRecord();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
