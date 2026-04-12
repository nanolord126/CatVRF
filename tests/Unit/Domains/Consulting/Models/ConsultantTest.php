<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Consulting\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Consultant model.
 *
 * @covers \App\Domains\Consulting\Models\Consultant
 */
final class ConsultantTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Consulting\Models\Consultant::class
        );
        $this->assertTrue($reflection->isFinal(), 'Consultant must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Consulting\Models\Consultant();
        $this->assertNotEmpty($model->getFillable(), 'Consultant must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Consulting\Models\Consultant();
        $this->assertNotEmpty($model->getCasts(), 'Consultant must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Consulting\Models\Consultant();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
