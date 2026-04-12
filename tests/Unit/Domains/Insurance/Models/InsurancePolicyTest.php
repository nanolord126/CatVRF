<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Insurance\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InsurancePolicy model.
 *
 * @covers \App\Domains\Insurance\Models\InsurancePolicy
 */
final class InsurancePolicyTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Insurance\Models\InsurancePolicy::class
        );
        $this->assertTrue($reflection->isFinal(), 'InsurancePolicy must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Insurance\Models\InsurancePolicy();
        $this->assertNotEmpty($model->getFillable(), 'InsurancePolicy must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Insurance\Models\InsurancePolicy();
        $this->assertNotEmpty($model->getCasts(), 'InsurancePolicy must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Insurance\Models\InsurancePolicy();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
