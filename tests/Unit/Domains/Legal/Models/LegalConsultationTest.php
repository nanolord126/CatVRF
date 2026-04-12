<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Legal\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LegalConsultation model.
 *
 * @covers \App\Domains\Legal\Models\LegalConsultation
 */
final class LegalConsultationTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Legal\Models\LegalConsultation::class
        );
        $this->assertTrue($reflection->isFinal(), 'LegalConsultation must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Legal\Models\LegalConsultation();
        $this->assertNotEmpty($model->getFillable(), 'LegalConsultation must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Legal\Models\LegalConsultation();
        $this->assertNotEmpty($model->getCasts(), 'LegalConsultation must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Legal\Models\LegalConsultation();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
