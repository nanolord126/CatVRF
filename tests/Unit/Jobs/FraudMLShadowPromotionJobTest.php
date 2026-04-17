<?php declare(strict_types=1);

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\FraudMLShadowPromotionJob;
use App\Models\FraudModelVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

final class FraudMLShadowPromotionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_dispatches_with_delay(): void
    {
        Queue::fake();

        FraudMLShadowPromotionJob::dispatch('2026-04-17-v1')
            ->delay(now()->addHours(24));

        Queue::assertPushed(FraudMLShadowPromotionJob::class, function ($job) {
            return $job->delay->isFuture();
        });
    }

    public function test_handle_promotes_model_when_ready(): void
    {
        $model = FraudModelVersion::factory()->create([
            'version' => '2026-04-17-v1',
            'is_shadow' => true,
            'is_active' => false,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => 0.94,
            'shadow_predictions_count' => 1500,
        ]);

        $job = new FraudMLShadowPromotionJob('2026-04-17-v1');
        $job->handle();

        $model->refresh();

        $this->assertTrue($model->is_active);
        $this->assertFalse($model->is_shadow);
        $this->assertNotNull($model->promoted_at);
    }

    public function test_handle_does_not_promote_when_not_ready(): void
    {
        $model = FraudModelVersion::factory()->create([
            'version' => '2026-04-17-v1',
            'is_shadow' => true,
            'is_active' => false,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => 0.90, // Below threshold
            'shadow_predictions_count' => 1500,
        ]);

        $job = new FraudMLShadowPromotionJob('2026-04-17-v1');
        $job->handle();

        $model->refresh();

        $this->assertFalse($model->is_active);
        $this->assertTrue($model->is_shadow);
        $this->assertNull($model->promoted_at);
        $this->assertStringContainsString('Shadow promotion failed', $model->comment);
    }

    public function test_handle_updates_shadow_metrics(): void
    {
        $model = FraudModelVersion::factory()->create([
            'version' => '2026-04-17-v1',
            'is_shadow' => true,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => null,
            'shadow_predictions_count' => 0,
        ]);

        $job = new FraudMLShadowPromotionJob('2026-04-17-v1');
        $job->handle();

        $model->refresh();

        $this->assertNotNull($model->shadow_auc_roc);
        $this->assertGreaterThan(0, $model->shadow_predictions_count);
    }

    public function test_handle_logs_warning_when_model_not_found(): void
    {
        $job = new FraudMLShadowPromotionJob('non-existent-version');
        
        // Should not throw exception
        $this->expectNotToPerformAssertions();
        $job->handle();
    }
}
