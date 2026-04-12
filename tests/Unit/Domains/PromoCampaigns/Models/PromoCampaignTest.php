<?php declare(strict_types=1);

namespace Tests\Unit\Domains\PromoCampaigns\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PromoCampaign model.
 *
 * @covers \App\Domains\PromoCampaigns\Models\PromoCampaign
 */
final class PromoCampaignTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\PromoCampaigns\Models\PromoCampaign::class
        );
        $this->assertTrue($reflection->isFinal(), 'PromoCampaign must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\PromoCampaigns\Models\PromoCampaign();
        $this->assertNotEmpty($model->getFillable(), 'PromoCampaign must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\PromoCampaigns\Models\PromoCampaign();
        $this->assertNotEmpty($model->getCasts(), 'PromoCampaign must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\PromoCampaigns\Models\PromoCampaign();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
