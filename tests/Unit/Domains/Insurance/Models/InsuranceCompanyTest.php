<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Insurance\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InsuranceCompany model.
 *
 * @covers \App\Domains\Insurance\Models\InsuranceCompany
 */
final class InsuranceCompanyTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Insurance\Models\InsuranceCompany::class
        );
        $this->assertTrue($reflection->isFinal(), 'InsuranceCompany must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Insurance\Models\InsuranceCompany();
        $this->assertNotEmpty($model->getFillable(), 'InsuranceCompany must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Insurance\Models\InsuranceCompany();
        $this->assertNotEmpty($model->getCasts(), 'InsuranceCompany must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Insurance\Models\InsuranceCompany();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
