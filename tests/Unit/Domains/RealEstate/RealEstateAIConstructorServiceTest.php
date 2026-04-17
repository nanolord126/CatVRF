<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\AI\RealEstateAIConstructorService;
use App\Domains\RealEstate\Models\Property;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class RealEstateAIConstructorServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstateAIConstructorService $service;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealEstateAIConstructorService::class);
        $this->tenant = Tenant::factory()->create();
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
            'features' => [
                'rooms' => 2,
                'floor' => 5,
                'total_floors' => 12,
                'renovated' => true,
                'balcony' => true,
                'parking' => true,
                'elevator' => true,
                'near_metro' => true,
                'near_park' => true,
                'location_score' => 0.8,
                'condition_score' => 0.85,
            ],
        ]);
    }

    public function test_generate_property_description_returns_valid_string(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $description = $this->service->generatePropertyDescription($this->property, $correlationId);

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('квартира', $description);
        $this->assertStringContainsString('75.5', $description);
        $this->assertStringContainsString('ремонт', $description);
    }

    public function test_generate_property_description_caches_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        $firstCall = $this->service->generatePropertyDescription($this->property, $correlationId);
        $secondCall = $this->service->generatePropertyDescription($this->property, $correlationId);

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_generate_property_tags_returns_expected_tags(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tags = $this->service->generatePropertyTags($this->property, $correlationId);

        $this->assertIsArray($tags);
        $this->assertContains('luxury', $tags);
        $this->assertContains('apartment', $tags);
        $this->assertContains('elevator', $tags);
        $this->assertContains('parking', $tags);
        $this->assertContains('balcony', $tags);
        $this->assertContains('renovated', $tags);
    }

    public function test_generate_property_tags_includes_budget_friendly_for_low_price(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 3000000.00,
            'type' => 'apartment',
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $tags = $this->service->generatePropertyTags($property, $correlationId);

        $this->assertContains('budget_friendly', $tags);
    }

    public function test_calculate_property_score_returns_valid_score(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $score = $this->service->calculatePropertyScore($this->property, $correlationId);

        $this->assertIsArray($score);
        $this->assertArrayHasKey('overall_score', $score);
        $this->assertArrayHasKey('location_score', $score);
        $this->assertArrayHasKey('price_score', $score);
        $this->assertArrayHasKey('features_score', $score);
        $this->assertArrayHasKey('condition_score', $score);
        $this->assertArrayHasKey('recommendation', $score);
        $this->assertArrayHasKey('improvements', $score);

        $this->assertGreaterThanOrEqual(0, $score['overall_score']);
        $this->assertLessThanOrEqual(1, $score['overall_score']);
    }

    public function test_calculate_property_score_caches_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        $firstCall = $this->service->calculatePropertyScore($this->property, $correlationId);
        $secondCall = $this->service->calculatePropertyScore($this->property, $correlationId);

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_calculate_property_score_returns_valid_recommendation(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $score = $this->service->calculatePropertyScore($this->property, $correlationId);

        $this->assertContains($score['recommendation'], ['excellent', 'good', 'fair', 'poor']);
    }

    public function test_generate_similar_properties_returns_array(): void
    {
        Property::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'price' => 9500000.00,
            'status' => 'active',
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $similar = $this->service->generateSimilarProperties($this->property, 3, $correlationId);

        $this->assertIsArray($similar);
        $this->assertLessThanOrEqual(3, count($similar));
        $this->assertGreaterThanOrEqual(0, count($similar));
    }

    public function test_generate_similar_properties_filters_by_type(): void
    {
        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'house',
            'price' => 9800000.00,
            'status' => 'active',
        ]);

        Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'price' => 9800000.00,
            'status' => 'active',
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $similar = $this->service->generateSimilarProperties($this->property, 5, $correlationId);

        foreach ($similar as $property) {
            $this->assertEquals('apartment', $property['type']);
        }
    }

    public function test_generate_investment_analysis_returns_valid_data(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $analysis = $this->service->generateInvestmentAnalysis($this->property, $correlationId);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('purchase_price', $analysis);
        $this->assertArrayHasKey('estimated_monthly_rent', $analysis);
        $this->assertArrayHasKey('estimated_annual_rent', $analysis);
        $this->assertArrayHasKey('rental_yield', $analysis);
        $this->assertArrayHasKey('appreciation_rate', $analysis);
        $this->assertArrayHasKey('5_year_projection', $analysis);
        $this->assertArrayHasKey('10_year_projection', $analysis);
        $this->assertArrayHasKey('payback_period_years', $analysis);
        $this->assertArrayHasKey('risk_level', $analysis);
        $this->assertArrayHasKey('recommendation', $analysis);

        $this->assertGreaterThan(0, $analysis['estimated_monthly_rent']);
        $this->assertGreaterThan(0, $analysis['rental_yield']);
        $this->assertGreaterThan($analysis['purchase_price'], $analysis['5_year_projection']);
        $this->assertGreaterThan($analysis['5_year_projection'], $analysis['10_year_projection']);
    }

    public function test_generate_investment_analysis_caches_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        $firstCall = $this->service->generateInvestmentAnalysis($this->property, $correlationId);
        $secondCall = $this->service->generateInvestmentAnalysis($this->property, $correlationId);

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_generate_investment_analysis_returns_valid_risk_level(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $analysis = $this->service->generateInvestmentAnalysis($this->property, $correlationId);

        $this->assertContains($analysis['risk_level'], ['low', 'medium', 'high']);
    }

    public function test_generate_investment_analysis_returns_valid_recommendation(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $analysis = $this->service->generateInvestmentAnalysis($this->property, $correlationId);

        $this->assertContains($analysis['recommendation'], ['strong_buy', 'buy', 'hold', 'avoid']);
    }

    public function test_clear_property_cache_clears_all_cache_keys(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        
        $this->service->generatePropertyDescription($this->property, $correlationId);
        $this->service->generatePropertyTags($this->property, $correlationId);
        $this->service->calculatePropertyScore($this->property, $correlationId);
        $this->service->generateSimilarProperties($this->property, 5, $correlationId);
        $this->service->generateInvestmentAnalysis($this->property, $correlationId);

        $this->service->clearPropertyCache($this->property->id);

        $cacheKey = "ai:description:{$this->property->id}";
        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_generate_property_description_for_house(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'house',
            'area_sqm' => 150.0,
            'features' => ['garden' => true],
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $description = $this->service->generatePropertyDescription($property, $correlationId);

        $this->assertStringContainsString('дом', $description);
    }

    public function test_generate_property_description_for_commercial(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'commercial',
            'area_sqm' => 200.0,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $description = $this->service->generatePropertyDescription($property, $correlationId);

        $this->assertStringContainsString('коммерческое помещение', $description);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
