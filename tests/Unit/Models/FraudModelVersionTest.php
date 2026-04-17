<?php declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\FraudModelVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

final class FraudModelVersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_shadow_mode_sets_flags_and_timestamp(): void
    {
        $model = FraudModelVersion::factory()->create([
            'is_shadow' => false,
            'is_active' => false,
            'shadow_started_at' => null,
        ]);

        $model->startShadowMode();

        $this->assertTrue($model->is_shadow);
        $this->assertFalse($model->is_active);
        $this->assertNotNull($model->shadow_started_at);
    }

    public function test_promote_to_active_deactivates_other_models(): void
    {
        // Create existing active model
        $existingActive = FraudModelVersion::factory()->create([
            'is_active' => true,
            'is_shadow' => false,
            'promoted_at' => now()->subHours(2),
        ]);

        // Create new model to promote
        $newModel = FraudModelVersion::factory()->create([
            'is_shadow' => true,
            'is_active' => false,
            'shadow_started_at' => now()->subHours(25),
        ]);

        $newModel->promoteToActive();

        // Refresh from DB
        $existingActive->refresh();
        $newModel->refresh();

        $this->assertFalse($existingActive->is_active);
        $this->assertTrue($existingActive->is_rollback_candidate);

        $this->assertTrue($newModel->is_active);
        $this->assertFalse($newModel->is_shadow);
        $this->assertNotNull($newModel->promoted_at);
    }

    public function test_is_ready_for_promotion_checks_all_conditions(): void
    {
        // Not in shadow mode
        $model1 = FraudModelVersion::factory()->create([
            'is_shadow' => false,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => 0.95,
            'shadow_predictions_count' => 1000,
        ]);
        $this->assertFalse($model1->isReadyForPromotion());

        // Shadow period not complete
        $model2 = FraudModelVersion::factory()->create([
            'is_shadow' => true,
            'shadow_started_at' => now()->subHours(12),
            'shadow_auc_roc' => 0.95,
            'shadow_predictions_count' => 1000,
        ]);
        $this->assertFalse($model2->isReadyForPromotion());

        // Shadow metrics not collected
        $model3 = FraudModelVersion::factory()->create([
            'is_shadow' => true,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => null,
            'shadow_predictions_count' => 1000,
        ]);
        $this->assertFalse($model3->isReadyForPromotion());

        // Insufficient predictions
        $model4 = FraudModelVersion::factory()->create([
            'is_shadow' => true,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => 0.95,
            'shadow_predictions_count' => 50,
        ]);
        $this->assertFalse($model4->isReadyForPromotion());

        // AUC below threshold
        $model5 = FraudModelVersion::factory()->create([
            'is_shadow' => true,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => 0.90,
            'shadow_predictions_count' => 1000,
        ]);
        $this->assertFalse($model5->isReadyForPromotion());

        // All conditions met
        $model6 = FraudModelVersion::factory()->create([
            'is_shadow' => true,
            'shadow_started_at' => now()->subHours(30),
            'shadow_auc_roc' => 0.94,
            'shadow_predictions_count' => 1000,
        ]);
        $this->assertTrue($model6->isReadyForPromotion());
    }

    public function test_get_active_returns_latest_active_model(): void
    {
        FraudModelVersion::factory()->create([
            'is_active' => true,
            'is_shadow' => false,
            'promoted_at' => now()->subHours(5),
        ]);

        $latest = FraudModelVersion::factory()->create([
            'is_active' => true,
            'is_shadow' => false,
            'promoted_at' => now()->subHour(),
        ]);

        $active = FraudModelVersion::getActive();

        $this->assertNotNull($active);
        $this->assertEquals($latest->id, $active->id);
    }

    public function test_get_active_returns_null_when_no_active_model(): void
    {
        FraudModelVersion::factory()->create([
            'is_active' => false,
            'is_shadow' => true,
        ]);

        $active = FraudModelVersion::getActive();

        $this->assertNull($active);
    }

    public function test_rollback_to_previous_promotes_rollback_candidate(): void
    {
        // Create rollback candidate
        $previous = FraudModelVersion::factory()->create([
            'is_active' => false,
            'is_shadow' => false,
            'is_rollback_candidate' => true,
            'promoted_at' => now()->subDays(2),
        ]);

        // Create current active
        $current = FraudModelVersion::factory()->create([
            'is_active' => true,
            'is_shadow' => false,
            'promoted_at' => now()->subHours(1),
        ]);

        $rolledBack = FraudModelVersion::rollbackToPrevious();

        $this->assertNotNull($rolledBack);
        $this->assertEquals($previous->id, $rolledBack->id);

        $previous->refresh();
        $current->refresh();

        $this->assertTrue($previous->is_active);
        $this->assertFalse($previous->is_rollback_candidate);
        $this->assertEquals($current->id, $previous->rolled_back_from_id);

        $this->assertFalse($current->is_active);
    }

    public function test_rollback_to_previous_returns_null_when_no_candidate(): void
    {
        FraudModelVersion::factory()->create([
            'is_active' => true,
            'is_shadow' => false,
            'is_rollback_candidate' => false,
        ]);

        $rolledBack = FraudModelVersion::rollbackToPrevious();

        $this->assertNull($rolledBack);
    }
}
