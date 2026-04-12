<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Advertising\Models;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AdCampaign model.
 *
 * @covers \App\Domains\Advertising\Models\AdCampaign
 */
final class AdCampaignTest extends TestCase
{
    public function test_class_is_final(): void
    {
        $reflection = new \ReflectionClass(
            \App\Domains\Advertising\Models\AdCampaign::class
        );
        $this->assertTrue($reflection->isFinal(), 'AdCampaign must be final');
    }

    public function test_has_fillable_properties(): void
    {
        $model = new \App\Domains\Advertising\Models\AdCampaign();
        $this->assertNotEmpty($model->getFillable(), 'AdCampaign must have fillable');
        $this->assertContains('correlation_id', $model->getFillable(), 'Must have correlation_id');
    }

    public function test_has_casts(): void
    {
        $model = new \App\Domains\Advertising\Models\AdCampaign();
        $this->assertNotEmpty($model->getCasts(), 'AdCampaign must have casts');
    }

    public function test_has_tenant_id_in_fillable(): void
    {
        $model = new \App\Domains\Advertising\Models\AdCampaign();
        $this->assertContains('tenant_id', $model->getFillable(), 'Must have tenant_id');
    }
}
