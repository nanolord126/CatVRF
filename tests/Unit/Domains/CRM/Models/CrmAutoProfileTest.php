<?php declare(strict_types=1);

namespace Tests\Unit\Domains\CRM\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CrmAutoProfile model.
 *
 * @covers \App\Domains\CRM\Models\CrmAutoProfile
 */
final class CrmAutoProfileTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\CRM\Models\CrmAutoProfile::class
        );
        $this->assertTrue($reflection->isFinal(), 'CrmAutoProfile must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\CRM\Models\CrmAutoProfile();
        $this->assertNotEmpty($model->getFillable(), 'CrmAutoProfile must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\CRM\Models\CrmAutoProfile();
        $this->assertNotEmpty($model->getCasts(), 'CrmAutoProfile must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\CRM\Models\CrmAutoProfile();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
