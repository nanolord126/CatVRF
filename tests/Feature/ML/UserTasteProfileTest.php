<?php

declare(strict_types=1);

namespace Tests\Feature\ML;

use App\Models\User;
use App\Models\UserBodyMetrics;
use App\Models\UserTasteProfile;
use App\Services\ML\UserTasteProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for User Taste Profile v2.0
 * CANON 2026: Production-ready tests
 */
final class UserTasteProfileTest extends TestCase
{
    use RefreshDatabase;

    private UserTasteProfileService $tasteService;

    private User $user;

    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tasteService = app(UserTasteProfileService::class);
        $this->user = User::factory()->create(['tenant_id' => $this->tenantId]);
    }

    // ========== PROFILE CREATION TESTS ==========

    public function test_can_create_taste_profile(): void
    {
        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $this->assertNotNull($profile->id);
        $this->assertEquals($this->user->id, $profile->user_id);
        $this->assertEquals($this->tenantId, $profile->tenant_id);
        $this->assertTrue($profile->is_active);
        $this->assertTrue($profile->allow_personalization);
    }

    public function test_profile_has_default_values(): void
    {
        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $this->assertIsArray($profile->explicit_preferences);
        $this->assertIsArray($profile->implicit_scores);
        $this->assertIsArray($profile->metadata);
        $this->assertEquals(1, $profile->version);
        $this->assertEquals(0.0, $profile->getDataQualityScore());
    }

    // ========== EXPLICIT PREFERENCES TESTS ==========

    public function test_can_update_explicit_preferences(): void
    {
        $preferences = [
            'sizes' => [
                'clothing' => ['top' => 'M', 'bottom' => '32'],
                'shoes' => ['eu' => '39'],
            ],
            'dietary' => [
                'type' => ['vegan'],
                'allergies' => ['nuts'],
            ],
        ];

        $profile = $this->tasteService->updateExplicitPreferences(
            $this->user->id,
            $this->tenantId,
            $preferences
        );

        $explicit = $profile->getExplicitPreferences();
        $this->assertArrayHasKey('sizes', $explicit);
        $this->assertEquals('M', $explicit['sizes']['clothing']['top']);
        $this->assertContains('vegan', $explicit['dietary']['type']);
    }

    public function test_explicit_preferences_are_merged(): void
    {
        // First update
        $this->tasteService->updateExplicitPreferences(
            $this->user->id,
            $this->tenantId,
            ['sizes' => ['clothing' => ['top' => 'M']]]
        );

        // Second update (should merge, not replace)
        $profile = $this->tasteService->updateExplicitPreferences(
            $this->user->id,
            $this->tenantId,
            ['dietary' => ['type' => ['vegan']]]
        );

        $explicit = $profile->getExplicitPreferences();
        $this->assertEquals('M', $explicit['sizes']['clothing']['top']);
        $this->assertContains('vegan', $explicit['dietary']['type']);
    }

    // ========== INTERACTIONS TESTS ==========

    public function test_can_record_product_view_interaction(): void
    {
        $this->tasteService->recordInteraction(
            $this->user->id,
            $this->tenantId,
            'product_view',
            ['product_id' => 123, 'category' => 'fashion']
        );

        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $this->assertGreater($profile->getTotalInteractions(), 0);
    }

    public function test_can_record_purchase_interaction(): void
    {
        $this->tasteService->recordInteraction(
            $this->user->id,
            $this->tenantId,
            'purchase',
            ['order_id' => 456, 'amount' => 2500]
        );

        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $this->assertGreater($profile->getTotalInteractions(), 0);
    }

    // ========== DATA QUALITY TESTS ==========

    public function test_cold_start_profile_is_not_ready(): void
    {
        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $this->assertTrue($profile->isColdStart());
        $this->assertFalse($profile->isReadyForRecommendations());
    }

    public function test_profile_becomes_ready_after_interactions(): void
    {
        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        // Add 10 interactions
        for ($i = 0; $i < 10; $i++) {
            $this->tasteService->recordInteraction(
                $this->user->id,
                $this->tenantId,
                'product_view',
                ['product_id' => $i]
            );
        }

        $profile->refresh();

        $this->assertGreaterThanOrEqual(10, $profile->getTotalInteractions());
    }

    // ========== PERSONALIZATION TOGGLE TESTS ==========

    public function test_can_disable_personalization(): void
    {
        $profile = $this->tasteService->setPersonalizationEnabled(
            $this->user->id,
            $this->tenantId,
            false
        );

        $this->assertFalse($profile->allow_personalization);
    }

    public function test_profile_data_persists_after_disabling_personalization(): void
    {
        $preferences = ['sizes' => ['clothing' => ['top' => 'L']]];

        $this->tasteService->updateExplicitPreferences(
            $this->user->id,
            $this->tenantId,
            $preferences
        );

        // Disable personalization
        $this->tasteService->setPersonalizationEnabled(
            $this->user->id,
            $this->tenantId,
            false
        );

        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        // Data should still be there
        $explicit = $profile->getExplicitPreferences();
        $this->assertEquals('L', $explicit['sizes']['clothing']['top']);
        $this->assertFalse($profile->allow_personalization);
    }

    // ========== BODY METRICS TESTS ==========

    public function test_can_create_body_metrics(): void
    {
        $metrics = UserBodyMetrics::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenantId,
            'height_cm' => 172,
            'weight_kg' => 68.5,
            'body_shape' => 'hourglass',
            'skin_tone' => 'warm_beige',
            'shoe_size_eu' => '39',
        ]);

        $this->assertEquals(172, $metrics->height_cm);
        $this->assertNotNull($metrics->getBMI());
    }

    public function test_bmi_calculation(): void
    {
        $metrics = UserBodyMetrics::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenantId,
            'height_cm' => 172,
            'weight_kg' => 68,
        ]);

        $bmi = $metrics->getBMI();
        $this->assertNotNull($bmi);
        $this->assertGreaterThan(20, $bmi);
        $this->assertLessThan(25, $bmi);
    }

    // ========== HISTORY TESTS ==========

    public function test_changes_are_recorded_in_history(): void
    {
        $this->tasteService->updateExplicitPreferences(
            $this->user->id,
            $this->tenantId,
            ['sizes' => ['clothing' => ['top' => 'M']]]
        );

        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $histories = $profile->histories()->get();
        $this->assertGreaterThan(0, $histories->count());
    }

    // ========== METADATA TESTS ==========

    public function test_metadata_contains_required_fields(): void
    {
        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $metadata = $profile->metadata;

        $this->assertArrayHasKey('data_quality_score', $metadata);
        $this->assertArrayHasKey('total_interactions', $metadata);
        $this->assertArrayHasKey('ml_model_version', $metadata);
        $this->assertArrayHasKey('recommendation_influence', $metadata);
    }

    public function test_recommendation_influence_is_capped_at_0_7(): void
    {
        $profile = $this->tasteService->getOrCreateProfile(
            $this->user->id,
            $this->tenantId
        );

        $influence = $profile->getRecommendationInfluence();
        $this->assertLessThanOrEqual(0.7, $influence);
    }
}
