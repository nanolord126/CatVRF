<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Consulting\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ConsultingSession model.
 *
 * @covers \App\Domains\Consulting\Models\ConsultingSession
 */
final class ConsultingSessionTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Consulting\Models\ConsultingSession::class
        );
        $this->assertTrue($reflection->isFinal(), 'ConsultingSession must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Consulting\Models\ConsultingSession();
        $this->assertNotEmpty($model->getFillable(), 'ConsultingSession must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Consulting\Models\ConsultingSession();
        $this->assertNotEmpty($model->getCasts(), 'ConsultingSession must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Consulting\Models\ConsultingSession();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
