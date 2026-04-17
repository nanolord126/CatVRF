<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\RealEstatePredictiveScoringService;
use App\Domains\RealEstate\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;

final class RealEstatePredictiveScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstatePredictiveScoringService $service;
    private Tenant $tenant;
    private Property $property;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
            'metadata' => [
                'title_clear' => true,
                'no_liens' => true,
                'zoning_compliant' => true,
                'building_permit_valid' => true,
                'tax_clearance' => true,
                'ownership_verified' => true,
                'documents_complete' => true,
                'metro_distance_meters' => 1000,
                'school_distance_meters' => 500,
                'park_distance_meters' => 300,
                'infrastructure_score' => 0.8,
                'crime_rate' => 0.2,
                'blockchain_verified' => true,
                'smart_contract_address' => '0x' . str_repeat('0', 40),
                'virtual_tour_enabled' => true,
            ],
        ]);

        DB::table('user_profiles')->insert([
            'user_id' => $this->user->id,
            'estimated_income' => 500000.00,
            'existing_debt' => 50000.00,
        ]);

        $this->service = app(RealEstatePredictiveScoringService::class);
    }

    public function test_calculate_deal_score_returns_valid_scoring_b2c(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('credit_score', $result);
        $this->assertArrayHasKey('legal_score', $result);
        $this->assertArrayHasKey('liquidity_score', $result);
        $this->assertArrayHasKey('ml_fraud_score', $result);
        $this->assertArrayHasKey('ai_liquidity_score', $result);
        $this->assertArrayHasKey('recommendation', $result);
        $this->assertArrayHasKey('mortgage_rate_estimate', $result);
        $this->assertArrayHasKey('flash_discount_percent', $result);
        $this->assertArrayHasKey('dynamic_price', $result);
        $this->assertArrayHasKey('risk_factors', $result);
        $this->assertArrayHasKey('blockchain_verified', $result);
        $this->assertArrayHasKey('escrow_eligible', $result);
        $this->assertArrayHasKey('webrtc_enabled', $result);
        $this->assertArrayHasKey('crm_synced', $result);
        $this->assertArrayHasKey('is_b2b', $result);
        $this->assertFalse($result['is_b2b']);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(1, $result['overall_score']);
    }

    public function test_calculate_deal_score_b2b_includes_tiered_pricing(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            true,
            $correlationId
        );

        $this->assertTrue($result['is_b2b']);
        $this->assertArrayHasKey('dynamic_price', $result);
        $this->assertArrayHasKey('b2b_discount', $result['dynamic_price']);
        $this->assertGreaterThan(0, $result['dynamic_price']['b2b_discount']);
    }

    public function test_calculate_deal_score_caches_result_with_idempotency(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $idempotencyKey = \Illuminate\Support\Str::uuid()->toString();

        $firstCall = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId,
            $idempotencyKey
        );

        $secondCall = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId,
            $idempotencyKey
        );

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_calculate_deal_score_includes_ml_fraud_detection(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('ml_fraud_score', $result);
        $this->assertArrayHasKey('score', $result['ml_fraud_score']);
        $this->assertArrayHasKey('fraud_risk', $result['ml_fraud_score']);
        $this->assertArrayHasKey('factors', $result['ml_fraud_score']);
        $this->assertGreaterThanOrEqual(0, $result['ml_fraud_score']['score']);
        $this->assertLessThanOrEqual(1, $result['ml_fraud_score']['score']);
    }

    public function test_calculate_deal_score_includes_ai_liquidity_analysis(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('ai_liquidity_score', $result);
        $this->assertArrayHasKey('score', $result['ai_liquidity_score']);
        $this->assertArrayHasKey('factors', $result['ai_liquidity_score']);
        $this->assertArrayHasKey('ai_demand_prediction', $result['ai_liquidity_score']['factors']);
        $this->assertArrayHasKey('ai_price_optimization', $result['ai_liquidity_score']['factors']);
    }

    public function test_calculate_deal_score_includes_flash_discount(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $propertyLowScore = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 50.0,
            'price' => 15000000.00,
            'metadata' => [
                'title_clear' => false,
                'no_liens' => false,
            ],
        ]);

        $result = $this->service->calculateDealScore(
            $propertyLowScore,
            $this->user->id,
            15000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('flash_discount_percent', $result);
        $this->assertGreaterThanOrEqual(0, $result['flash_discount_percent']);
        $this->assertLessThanOrEqual(0.15, $result['flash_discount_percent']);
    }

    public function test_calculate_deal_score_includes_blockchain_verification(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('blockchain_verified', $result);
        $this->assertArrayHasKey('blockchain_details', $result);
        $this->assertArrayHasKey('smart_contract_address', $result['blockchain_details']);
        $this->assertTrue($result['blockchain_verified']);
    }

    public function test_calculate_deal_score_includes_escrow_eligibility(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('escrow_eligible', $result);
        $this->assertArrayHasKey('escrow_details', $result);
        $this->assertArrayHasKey('escrow_fee', $result['escrow_details']);
        $this->assertArrayHasKey('max_hold_duration_days', $result['escrow_details']);
        $this->assertIsBool($result['escrow_eligible']);
    }

    public function test_calculate_deal_score_includes_webrtc_eligibility(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('webrtc_enabled', $result);
        $this->assertIsBool($result['webrtc_enabled']);
    }

    public function test_calculate_deal_score_includes_crm_sync(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertArrayHasKey('crm_synced', $result);
        $this->assertArrayHasKey('crm_details', $result);
        $this->assertIsBool($result['crm_synced']);
    }

    public function test_get_user_eligibility_returns_valid_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getUserEligibility(
            1,
            10000000.00,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('requested_amount', $result);
        $this->assertArrayHasKey('eligibility_score', $result);
        $this->assertArrayHasKey('is_eligible', $result);
        $this->assertArrayHasKey('max_eligible_amount', $result);
        $this->assertIsBool($result['is_eligible']);
    }

    public function test_calculate_bulk_scores_returns_multiple_scores_with_ai_recommendations(): void
    {
        $property2 = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 80.0,
            'price' => 12000000.00,
            'metadata' => [
                'title_clear' => true,
                'no_liens' => true,
            ],
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateBulkScores(
            [$this->property->id, $property2->id],
            $this->user->id,
            false,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_scores', $result);
        $this->assertArrayHasKey('total_properties', $result);
        $this->assertEquals(2, $result['total_properties']);
        $this->assertCount(2, $result['property_scores']);
        $this->assertArrayHasKey('ai_recommendations', $result['property_scores'][$this->property->id]);
    }

    public function test_get_user_eligibility_returns_valid_result_b2c(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getUserEligibility(
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('requested_amount', $result);
        $this->assertArrayHasKey('eligibility_score', $result);
        $this->assertArrayHasKey('is_eligible', $result);
        $this->assertArrayHasKey('max_eligible_amount', $result);
        $this->assertArrayHasKey('is_b2b', $result);
        $this->assertArrayHasKey('fraud_risk_score', $result);
        $this->assertArrayHasKey('user_behavior_type', $result);
        $this->assertFalse($result['is_b2b']);
        $this->assertIsBool($result['is_eligible']);
    }

    public function test_get_user_eligibility_includes_b2b_tier_and_credit_limit(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getUserEligibility(
            $this->user->id,
            10000000.00,
            true,
            $correlationId
        );

        $this->assertTrue($result['is_b2b']);
        $this->assertArrayHasKey('b2b_tier', $result);
        $this->assertArrayHasKey('credit_limit', $result);
        $this->assertGreaterThan(0, $result['credit_limit']);
    }

    public function test_calculate_deal_score_recommendation_includes_manual_review(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $propertyLowScore = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 30.0,
            'price' => 25000000.00,
            'metadata' => [
                'title_clear' => false,
                'no_liens' => false,
                'zoning_compliant' => false,
            ],
        ]);

        $result = $this->service->calculateDealScore(
            $propertyLowScore,
            $this->user->id,
            25000000.00,
            false,
            $correlationId
        );

        $this->assertContains($result['recommendation'], ['approved', 'review', 'manual_review', 'declined']);
    }

    public function test_calculate_deal_score_dynamic_price_never_below_50_percent(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            true,
            $correlationId
        );

        $basePrice = $result['dynamic_price']['base_price'];
        $dynamicPrice = $result['dynamic_price']['dynamic_price'];
        
        $this->assertGreaterThanOrEqual($basePrice * 0.5, $dynamicPrice);
    }

    public function test_calculate_deal_score_b2b_mortgage_rate_lower(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $resultB2C = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            false,
            $correlationId
        );

        $correlationId2 = \Illuminate\Support\Str::uuid()->toString();
        $resultB2B = $this->service->calculateDealScore(
            $this->property,
            $this->user->id,
            10000000.00,
            true,
            $correlationId2
        );

        $this->assertLessThan($resultB2C['mortgage_rate_estimate'], $resultB2B['mortgage_rate_estimate']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
