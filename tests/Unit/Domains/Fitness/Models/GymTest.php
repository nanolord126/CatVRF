<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fitness\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gym model.
 *
 * @covers \App\Domains\Fitness\Models\Gym
 */
final class GymTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fitness\Models\Gym::class
        );
        $this->assertTrue($reflection->isFinal(), 'Gym must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Fitness\Models\Gym();
        $this->assertNotEmpty($model->getFillable(), 'Gym must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Fitness\Models\Gym();
        $this->assertNotEmpty($model->getCasts(), 'Gym must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Fitness\Models\Gym();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
