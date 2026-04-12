<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Fitness\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Membership model.
 *
 * @covers \App\Domains\Fitness\Models\Membership
 */
final class MembershipTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Fitness\Models\Membership::class
        );
        $this->assertTrue($reflection->isFinal(), 'Membership must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Fitness\Models\Membership();
        $this->assertNotEmpty($model->getFillable(), 'Membership must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Fitness\Models\Membership();
        $this->assertNotEmpty($model->getCasts(), 'Membership must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Fitness\Models\Membership();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
