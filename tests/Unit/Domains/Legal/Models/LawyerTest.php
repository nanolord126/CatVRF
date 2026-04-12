<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Legal\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Lawyer model.
 *
 * @covers \App\Domains\Legal\Models\Lawyer
 */
final class LawyerTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Legal\Models\Lawyer::class
        );
        $this->assertTrue($reflection->isFinal(), 'Lawyer must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Legal\Models\Lawyer();
        $this->assertNotEmpty($model->getFillable(), 'Lawyer must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Legal\Models\Lawyer();
        $this->assertNotEmpty($model->getCasts(), 'Lawyer must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Legal\Models\Lawyer();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
