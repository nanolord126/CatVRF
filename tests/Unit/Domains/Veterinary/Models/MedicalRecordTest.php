<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Veterinary\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MedicalRecord model.
 *
 * @covers \App\Domains\Veterinary\Models\MedicalRecord
 */
final class MedicalRecordTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Veterinary\Models\MedicalRecord::class
        );
        $this->assertTrue($reflection->isFinal(), 'MedicalRecord must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Veterinary\Models\MedicalRecord();
        $this->assertNotEmpty($model->getFillable(), 'MedicalRecord must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Veterinary\Models\MedicalRecord();
        $this->assertNotEmpty($model->getCasts(), 'MedicalRecord must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Veterinary\Models\MedicalRecord();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
