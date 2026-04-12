<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Beauty\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BeautyService model.
 *
 * @covers \App\Domains\Beauty\Models\BeautyService
 */
final class BeautyServiceTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Beauty\Models\BeautyService::class
        );
        $this->assertTrue($reflection->isFinal(), 'BeautyService must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Beauty\Models\BeautyService();
        $this->assertNotEmpty($model->getFillable(), 'BeautyService must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Beauty\Models\BeautyService();
        $this->assertNotEmpty($model->getCasts(), 'BeautyService must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Beauty\Models\BeautyService();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
