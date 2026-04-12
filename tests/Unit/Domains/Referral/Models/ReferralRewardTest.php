<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Referral\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ReferralReward model.
 *
 * @covers \App\Domains\Referral\Models\ReferralReward
 */
final class ReferralRewardTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Referral\Models\ReferralReward::class
        );
        $this->assertTrue($reflection->isFinal(), 'ReferralReward must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Referral\Models\ReferralReward();
        $this->assertNotEmpty($model->getFillable(), 'ReferralReward must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Referral\Models\ReferralReward();
        $this->assertNotEmpty($model->getCasts(), 'ReferralReward must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Referral\Models\ReferralReward();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
