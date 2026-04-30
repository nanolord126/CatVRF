<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Common\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\Common\Models\CommonEntity;

/**
 * Unit tests for CommonEntity model.
 *
 * @covers \App\Domains\Common\Models\CommonEntity
 */
final class CommonEntityTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            CommonEntity::class
        );
        $this->assertTrue($reflection->isFinal(), 'CommonEntity must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new CommonEntity();
        $this->assertNotEmpty($model->getFillable(), 'CommonEntity must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new CommonEntity();
        $this->assertNotEmpty($model->getCasts(), 'CommonEntity must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new CommonEntity();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
