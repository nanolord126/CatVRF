<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Payment\Models;

use App\Domains\Payment\Enums\PaymentProvider;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\PaymentRecord;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/**
 * Unit-тесты для модели PaymentRecord.
 */
final class PaymentRecordModelTest extends TestCase
{
    public function test_model_is_final(): void
    {
        $ref = new \ReflectionClass(PaymentRecord::class);
        $this->assertTrue($ref->isFinal());
    }

    public function test_model_extends_eloquent(): void
    {
        $this->assertTrue(is_subclass_of(PaymentRecord::class, Model::class));
    }

    public function test_table_name(): void
    {
        $model = (new \ReflectionClass(PaymentRecord::class))->newInstanceWithoutConstructor();
        $this->assertSame('payment_transactions', $model->getTable());
    }

    public function test_fillable_contains_required_fields(): void
    {
        $model = (new \ReflectionClass(PaymentRecord::class))->newInstanceWithoutConstructor();
        $fillable = $model->getFillable();

        $required = [
            'tenant_id',
            'business_group_id',
            'uuid',
            'idempotency_key',
            'provider_code',
            'status',
            'amount_kopecks',
            'is_hold',
            'correlation_id',
        ];

        foreach ($required as $field) {
            $this->assertContains($field, $fillable, "Missing fillable: {$field}");
        }
    }

    public function test_casts_contain_enums_and_types(): void
    {
        $model = (new \ReflectionClass(PaymentRecord::class))->newInstanceWithoutConstructor();
        $casts = $model->getCasts();

        // provider_code → PaymentProvider enum
        $this->assertArrayHasKey('provider_code', $casts);
        $this->assertStringContainsString('PaymentProvider', $casts['provider_code']);

        // status → PaymentStatus enum
        $this->assertArrayHasKey('status', $casts);
        $this->assertStringContainsString('PaymentStatus', $casts['status']);

        // amount_kopecks → integer
        $this->assertArrayHasKey('amount_kopecks', $casts);
        $this->assertSame('integer', $casts['amount_kopecks']);

        // is_hold → boolean
        $this->assertArrayHasKey('is_hold', $casts);
        $this->assertSame('boolean', $casts['is_hold']);

        // JSON fields
        $this->assertArrayHasKey('provider_response', $casts);
        $this->assertArrayHasKey('tags', $casts);
        $this->assertArrayHasKey('metadata', $casts);
    }

    public function test_has_tenant_relation(): void
    {
        $ref = new \ReflectionClass(PaymentRecord::class);
        $this->assertTrue($ref->hasMethod('tenant'));
    }

    public function test_has_business_group_relation(): void
    {
        $ref = new \ReflectionClass(PaymentRecord::class);
        $this->assertTrue($ref->hasMethod('businessGroup'));
    }

    public function test_has_amount_rubles_attribute(): void
    {
        $model = (new \ReflectionClass(PaymentRecord::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(Model::class, 'attributes'))->setValue($model, [
            'amount_kopecks' => 150000,
        ]);

        $this->assertSame(1500.00, $model->amount_rubles);
    }

    public function test_has_can_transition_to_method(): void
    {
        $ref = new \ReflectionClass(PaymentRecord::class);
        $this->assertTrue($ref->hasMethod('canTransitionTo'));
    }

    public function test_has_is_final_method(): void
    {
        $ref = new \ReflectionClass(PaymentRecord::class);
        $this->assertTrue($ref->hasMethod('isFinal'));
    }

    public function test_has_booted_method(): void
    {
        $ref = new \ReflectionClass(PaymentRecord::class);
        $this->assertTrue($ref->hasMethod('booted'));
    }

    public function test_model_has_strict_types(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Models/PaymentRecord.php');
        $this->assertIsString($src);
        $this->assertStringContainsString('declare(strict_types=1);', $src);
    }

    public function test_model_no_facades(): void
    {
        $src = file_get_contents(__DIR__ . '/../../../../../app/Domains/Payment/Models/PaymentRecord.php');
        $this->assertIsString($src);
        $this->assertStringNotContainsString('use Illuminate\\Support\\Facades\\', $src);
    }
}
