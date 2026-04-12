<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CorporateContract model.
 *
 * @covers \App\Domains\Education\Models\CorporateContract
 */
final class CorporateContractTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Education\Models\CorporateContract::class
        );
        $this->assertTrue($reflection->isFinal(), 'CorporateContract must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Education\Models\CorporateContract();
        $this->assertNotEmpty($model->getFillable(), 'CorporateContract must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Education\Models\CorporateContract();
        $this->assertNotEmpty($model->getCasts(), 'CorporateContract must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Education\Models\CorporateContract();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
