<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\RealEstateDynamicPricingService;
use App\Domains\RealEstate\Models\Property;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class RealEstateDynamicPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstateDynamicPricingService $service;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealEstateDynamicPricingService::class);
        $this->tenant = Tenant::factory()->create();
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
        ]);
    }

    public function test_calculate_dynamic_price_returns_valid_pricing(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateDynamicPrice(
            $this->property,
            false,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('base_price', $result);
        $this->assertArrayHasKey('final_price', $result);
        $this->assertArrayHasKey('demand_score', $result);
        $this->assertArrayHasKey('price_multiplier', $result);
        $this->assertArrayHasKey('discount_percentage', $result);
        $this->assertArrayHasKey('is_flash_discount', $result);
        $this->assertArrayHasKey('is_high_demand', $result);
        $this->assertEquals(10000000.00, $result['base_price']);
    }

    public function test_calculate_dynamic_price_applies_b2b_discount(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $b2cResult = $this->service->calculateDynamicPrice(
            $this->property,
            false,
            1,
            $correlationId
        );

        $b2bResult = $this->service->calculateDynamicPrice(
            $this->property,
            true,
            1,
            $correlationId
        );

        $this->assertLessThan($b2cResult['final_price'], $b2bResult['final_price']);
    }

    public function test_calculate_dynamic_price_caches_result(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $idempotencyKey = \Illuminate\Support\Str::uuid()->toString();

        $firstCall = $this->service->calculateDynamicPrice(
            $this->property,
            false,
            1,
            $correlationId,
            $idempotencyKey
        );

        $secondCall = $this->service->calculateDynamicPrice(
            $this->property,
            false,
            1,
            $correlationId,
            $idempotencyKey
        );

        $this->assertEquals($firstCall, $secondCall);
    }

    public function test_get_bulk_pricing_returns_multiple_prices(): void
    {
        $property2 = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 80.0,
            'price' => 12000000.00,
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getBulkPricing(
            [$this->property->id, $property2->id],
            false,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_pricing', $result);
        $this->assertArrayHasKey('total_properties', $result);
        $this->assertEquals(2, $result['total_properties']);
        $this->assertCount(2, $result['property_pricing']);
    }

    public function test_apply_flash_discount_updates_property(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->applyFlashDiscount(
            $this->property->id,
            10.0,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('original_price', $result);
        $this->assertArrayHasKey('discounted_price', $result);
        $this->assertArrayHasKey('discount_percentage', $result);
        $this->assertEquals(10.0, $result['discount_percentage']);
        $this->assertLessThan($result['original_price'], $result['discounted_price']);
    }

    public function test_apply_flash_discount_rejects_invalid_percentage(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Discount percentage must be between 0 and');

        $this->service->applyFlashDiscount(
            $this->property->id,
            20.0,
            1,
            $correlationId
        );
    }

    public function test_get_price_history_returns_historical_data(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getPriceHistory(
            $this->property->id,
            7,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('price_history', $result);
        $this->assertArrayHasKey('current_price', $result);
        $this->assertArrayHasKey('days_analyzed', $result);
        $this->assertEquals(7, $result['days_analyzed']);
        $this->assertCount(7, $result['price_history']);
    }

    public function test_get_market_comparison_returns_comparison_data(): void
    {
        Property::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 9500000.00,
            'status' => 'active',
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getMarketComparison(
            $this->property->id,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('property_id', $result);
        $this->assertArrayHasKey('property_price', $result);
        $this->assertArrayHasKey('market_avg_price', $result);
        $this->assertArrayHasKey('is_competitive', $result);
        $this->assertArrayHasKey('recommended_price', $result);
        $this->assertIsBool($result['is_competitive']);
    }

    public function test_get_market_comparison_returns_competitive_for_low_price(): void
    {
        Property::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 15000000.00,
            'status' => 'active',
        ]);

        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->getMarketComparison(
            $this->property->id,
            1,
            $correlationId
        );

        $this->assertTrue($result['is_competitive']);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
