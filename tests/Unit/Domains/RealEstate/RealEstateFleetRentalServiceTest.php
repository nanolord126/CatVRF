<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\RealEstate;

use Tests\TestCase;
use App\Domains\RealEstate\Services\RealEstateFleetRentalService;
use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\Models\B2BDeal;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class RealEstateFleetRentalServiceTest extends TestCase
{
    use RefreshDatabase;

    private RealEstateFleetRentalService $service;
    private Tenant $tenant;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RealEstateFleetRentalService::class);
        $this->tenant = Tenant::factory()->create();
        $this->property = Property::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'apartment',
            'area_sqm' => 75.5,
            'price' => 10000000.00,
            'status' => 'available',
        ]);
    }

    public function test_create_fleet_rental_deal_returns_valid_deal(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->assertInstanceOf(B2BDeal::class, $deal);
        $this->assertEquals($this->property->id, $deal->property_id);
        $this->assertEquals($this->tenant->id, $deal->tenant_id);
        $this->assertEquals($this->tenant->id, $deal->business_group_id);
        $this->assertEquals('fleet_rental', $deal->deal_type);
        $this->assertEquals(5, $deal->unit_count);
        $this->assertEquals(12, $deal->lease_term_months);
        $this->assertEquals('pending_approval', $deal->status);
    }

    public function test_create_fleet_rental_deal_applies_discount(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->assertGreaterThan(0, $deal->discount_rate);
        $this->assertLessThan($deal->base_price_per_unit, $deal->discounted_price_per_unit);
    }

    public function test_create_fleet_rental_deal_rejects_insufficient_units(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum unit count is 2');

        $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            1,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );
    }

    public function test_create_fleet_rental_deal_rejects_invalid_lease_term(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Minimum lease term is 3 months');

        $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            2,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );
    }

    public function test_approve_fleet_deal_changes_status(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $approvedDeal = $this->service->approveFleetDeal(
            $deal->id,
            1,
            $correlationId
        );

        $this->assertEquals('approved', $approvedDeal->status);
        $this->assertEquals(1, $approvedDeal->approved_by);
        $this->assertNotNull($approvedDeal->approved_at);
    }

    public function test_approve_fleet_deal_updates_property_status(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->service->approveFleetDeal(
            $deal->id,
            1,
            $correlationId
        );

        $this->property->refresh();
        $this->assertEquals('rented', $this->property->status);
    }

    public function test_approve_fleet_deal_rejects_non_pending_deal(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = B2BDeal::factory()->create([
            'status' => 'approved',
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Deal is not in pending approval status');

        $this->service->approveFleetDeal(
            $deal->id,
            1,
            $correlationId
        );
    }

    public function test_reject_fleet_deal_changes_status(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $rejectedDeal = $this->service->rejectFleetDeal(
            $deal->id,
            1,
            'Insufficient credit score',
            $correlationId
        );

        $this->assertEquals('rejected', $rejectedDeal->status);
        $this->assertEquals(1, $rejectedDeal->rejected_by);
        $this->assertEquals('Insufficient credit score', $rejectedDeal->rejection_reason);
    }

    public function test_reject_fleet_deal_restores_property_status(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->service->rejectFleetDeal(
            $deal->id,
            1,
            'Insufficient credit score',
            $correlationId
        );

        $this->property->refresh();
        $this->assertEquals('available', $this->property->status);
    }

    public function test_calculate_fleet_pricing_returns_valid_pricing(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $result = $this->service->calculateFleetPricing(
            5,
            12,
            5000000.00,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('unit_count', $result);
        $this->assertArrayHasKey('lease_term_months', $result);
        $this->assertArrayHasKey('base_price_per_unit', $result);
        $this->assertArrayHasKey('discount_rate', $result);
        $this->assertArrayHasKey('discounted_price_per_unit', $result);
        $this->assertArrayHasKey('total_monthly_price', $result);
        $this->assertArrayHasKey('total_contract_value', $result);
        $this->assertArrayHasKey('total_savings', $result);
        $this->assertEquals(5, $result['unit_count']);
        $this->assertEquals(12, $result['lease_term_months']);
        $this->assertGreaterThan(0, $result['discount_rate']);
    }

    public function test_get_active_fleet_deals_returns_deals(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->service->approveFleetDeal(
            $deal->id,
            1,
            $correlationId
        );

        $result = $this->service->getActiveFleetDeals(
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deals', $result);
        $this->assertArrayHasKey('total_deals', $result);
        $this->assertGreaterThanOrEqual(1, $result['total_deals']);
    }

    public function test_extend_fleet_deal_increases_lease_term(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            12,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->service->approveFleetDeal(
            $deal->id,
            1,
            $correlationId
        );

        $extendedDeal = $this->service->extendFleetDeal(
            $deal->id,
            6,
            1,
            $correlationId
        );

        $this->assertEquals(18, $extendedDeal->lease_term_months);
        $this->assertGreaterThan($deal->total_contract_value, $extendedDeal->total_contract_value);
    }

    public function test_extend_fleet_deal_rejects_excessive_extension(): void
    {
        $correlationId = \Illuminate\Support\Str::uuid()->toString();
        $deal = $this->service->createFleetRentalDeal(
            $this->property->id,
            $this->tenant->id,
            5,
            36,
            5000000.00,
            $this->tenant->id,
            1,
            $correlationId
        );

        $this->service->approveFleetDeal(
            $deal->id,
            1,
            $correlationId
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Maximum lease term exceeded');

        $this->service->extendFleetDeal(
            $deal->id,
            30,
            1,
            $correlationId
        );
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
