<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\PersonalDevelopment\Models;

use PHPUnit\Framework\TestCase;
use App\Domains\PersonalDevelopment\Models\Coach;

/**
 * Unit tests for Coach model.
 *
 * @covers \App\Domains\PersonalDevelopment\Models\Coach
 */
final class CoachTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            Coach::class
        );
        $this->assertTrue($reflection->isFinal(), 'Coach must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new Coach();
        $this->assertNotEmpty($model->getFillable(), 'Coach must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new Coach();
        $this->assertNotEmpty($model->getCasts(), 'Coach must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new Coach();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
