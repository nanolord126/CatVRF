<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Referral\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Referral model.
 *
 * @covers \App\Domains\Referral\Models\Referral
 */
final class ReferralTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Referral\Models\Referral::class
        );
        $this->assertTrue($reflection->isFinal(), 'Referral must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Referral\Models\Referral();
        $this->assertNotEmpty($model->getFillable(), 'Referral must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Referral\Models\Referral();
        $this->assertNotEmpty($model->getCasts(), 'Referral must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Referral\Models\Referral();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
