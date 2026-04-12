<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Freelance\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FreelanceContract model.
 *
 * @covers \App\Domains\Freelance\Models\FreelanceContract
 */
final class FreelanceContractTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Freelance\Models\FreelanceContract::class
        );
        $this->assertTrue($reflection->isFinal(), 'FreelanceContract must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Freelance\Models\FreelanceContract();
        $this->assertNotEmpty($model->getFillable(), 'FreelanceContract must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Freelance\Models\FreelanceContract();
        $this->assertNotEmpty($model->getCasts(), 'FreelanceContract must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Freelance\Models\FreelanceContract();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
