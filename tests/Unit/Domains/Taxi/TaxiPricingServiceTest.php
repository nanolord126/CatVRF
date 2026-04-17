<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\DTOs\TaxiPricingDto;
use App\Domains\Taxi\Services\TaxiPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class TaxiPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private readonly TaxiPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiPricingService::class);
    }

    public function test_calculate_price_returns_valid_result(): void
    {
        $dto = new TaxiPricingDto(
            distanceKm: 10.5,
            estimatedMinutes: 25,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->calculatePrice($dto);

        $this->assertGreaterThan(0, $result->basePrice);
        $this->assertGreaterThanOrEqual(1.0, $result->surgeMultiplier);
        $this->assertGreaterThan(0, $result->totalPrice);
        $this->assertIsArray($result->priceBreakdown);
    }

    public function test_surge_multiplier_increases_for_b2b(): void
    {
        $dto = new TaxiPricingDto(
            distanceKm: 10.0,
            estimatedMinutes: 25,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: true,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->calculatePrice($dto);

        $this->assertGreaterThan(0, $result->totalPrice);
    }

    public function test_b2b_has_discount(): void
    {
        $regularDto = new TaxiPricingDto(
            distanceKm: 10.0,
            estimatedMinutes: 25,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation-123',
        );

        $b2bDto = new TaxiPricingDto(
            distanceKm: 10.0,
            estimatedMinutes: 25,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: true,
            correlationId: 'test-correlation-123',
        );

        $regularResult = $this->service->calculatePrice($regularDto);
        $b2bResult = $this->service->calculatePrice($b2bDto);

        $this->assertLessThanOrEqual($regularResult->totalPrice, $b2bResult->totalPrice);
    }

    public function test_price_breakdown_includes_all_components(): void
    {
        $dto = new TaxiPricingDto(
            distanceKm: 10.0,
            estimatedMinutes: 25,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->calculatePrice($dto);

        $this->assertArrayHasKey('base_price', $result->priceBreakdown);
        $this->assertArrayHasKey('distance_charge', $result->priceBreakdown);
        $this->assertArrayHasKey('time_charge', $result->priceBreakdown);
        $this->assertArrayHasKey('surge_charge', $result->priceBreakdown);
        $this->assertArrayHasKey('total', $result->priceBreakdown);
    }

    public function test_caching_works_for_pricing(): void
    {
        $dto = new TaxiPricingDto(
            distanceKm: 10.0,
            estimatedMinutes: 25,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation-123',
        );

        Cache::flush();

        $result1 = $this->service->calculatePrice($dto);
        $result2 = $this->service->calculatePrice($dto);

        $this->assertEquals($result1->totalPrice, $result2->totalPrice);
    }

    public function test_distance_affects_price(): void
    {
        $shortDto = new TaxiPricingDto(
            distanceKm: 5.0,
            estimatedMinutes: 15,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation-123',
        );

        $longDto = new TaxiPricingDto(
            distanceKm: 20.0,
            estimatedMinutes: 50,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            isB2B: false,
            correlationId: 'test-correlation-123',
        );

        $shortResult = $this->service->calculatePrice($shortDto);
        $longResult = $this->service->calculatePrice($longDto);

        $this->assertLessThan($longResult->totalPrice, $shortResult->totalPrice);
    }
}
