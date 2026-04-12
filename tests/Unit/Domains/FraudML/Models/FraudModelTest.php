<?php declare(strict_types=1);

namespace Tests\Unit\Domains\FraudML\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FraudModel model.
 *
 * @covers \App\Domains\FraudML\Models\FraudModel
 */
final class FraudModelTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\FraudML\Models\FraudModel::class
        );
        $this->assertTrue($reflection->isFinal(), 'FraudModel must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\FraudML\Models\FraudModel();
        $this->assertNotEmpty($model->getFillable(), 'FraudModel must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\FraudML\Models\FraudModel();
        $this->assertNotEmpty($model->getCasts(), 'FraudModel must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\FraudML\Models\FraudModel();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
