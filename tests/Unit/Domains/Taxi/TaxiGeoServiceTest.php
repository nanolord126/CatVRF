<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\Services\TaxiGeoService;
use App\Domains\Taxi\Models\TaxiGeoZone;
use App\Domains\Taxi\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxiGeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxiGeoService $geoService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->geoService = new TaxiGeoService();
    }

    public function test_calculate_distance(): void
    {
        $distance = $this->geoService->calculateDistance(
            lat1: 55.7558,
            lon1: 37.6173, // Moscow
            lat2: 59.9343,
            lon2: 30.3351, // Saint Petersburg
        );

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(600000, $distance); // ~630km
        $this->assertLessThan(700000, $distance);
    }

    public function test_calculate_route(): void
    {
        $route = $this->geoService->calculateRoute(
            pickupLat: 55.7558,
            pickupLon: 37.6173,
            dropoffLat: 55.7600,
            dropoffLon: 37.6200,
            correlationId: 'test-correlation',
        );

        $this->assertIsArray($route);
        $this->assertArrayHasKey('pickup', $route);
        $this->assertArrayHasKey('dropoff', $route);
        $this->assertArrayHasKey('distance_meters', $route);
        $this->assertArrayHasKey('duration_seconds', $route);
        $this->assertArrayHasKey('duration_minutes', $route);
        $this->assertGreaterThan(0, $route['distance_meters']);
    }

    public function test_estimate_duration(): void
    {
        $duration = $this->geoService->estimateDuration(
            pickupLat: 55.7558,
            pickupLon: 37.6173,
            dropoffLat: 55.7600,
            dropoffLon: 37.6200,
            correlationId: 'test-correlation',
        );

        $this->assertIsInt($duration);
        $this->assertGreaterThan(0, $duration);
    }

    public function test_get_traffic_factor(): void
    {
        $factor = $this->geoService->getTrafficFactor(55.7558, 37.6173, 'test-correlation');

        $this->assertIsFloat($factor);
        $this->assertGreaterThan(0.4, $factor);
        $this->assertLessThanOrEqual(1.0, $factor);
    }

    public function test_create_geo_zone(): void
    {
        $zone = $this->geoService->createGeoZone([
            'name' => 'Test Zone',
            'type' => TaxiGeoZone::TYPE_CITY,
            'center_latitude' => 55.7558,
            'center_longitude' => 37.6173,
            'radius_meters' => 5000,
            'base_price_multiplier' => 1.2,
            'min_price_kopeki' => 20000,
            'max_price_kopeki' => 600000,
        ], 'test-correlation');

        $this->assertInstanceOf(TaxiGeoZone::class, $zone);
        $this->assertEquals('Test Zone', $zone->name);
        $this->assertEquals(TaxiGeoZone::TYPE_CITY, $zone->type);
        $this->assertEquals(1.2, $zone->base_price_multiplier);
    }

    public function test_zone_contains_point_with_radius(): void
    {
        $zone = TaxiGeoZone::factory()->create([
            'center_latitude' => 55.7558,
            'center_longitude' => 37.6173,
            'radius_meters' => 1000,
        ]);

        $this->assertTrue($zone->containsPoint(55.7558, 37.6173)); // Center point
        $this->assertTrue($zone->containsPoint(55.7560, 37.6175)); // Within radius
        $this->assertFalse($zone->containsPoint(55.7600, 37.6200)); // Outside radius
    }

    public function test_get_pricing_multipliers(): void
    {
        $multipliers = $this->geoService->getPricingMultipliers(55.7558, 37.6173);

        $this->assertIsArray($multipliers);
        $this->assertArrayHasKey('base_multiplier', $multipliers);
        $this->assertArrayHasKey('min_price_kopeki', $multipliers);
        $this->assertArrayHasKey('max_price_kopeki', $multipliers);
        $this->assertArrayHasKey('zone_name', $multipliers);
    }

    public function test_predict_pickup_eta(): void
    {
        $eta = $this->geoService->predictPickupETA(
            driverLat: 55.7558,
            driverLon: 37.6173,
            pickupLat: 55.7600,
            pickupLon: 37.6200,
            correlationId: 'test-correlation',
        );

        $this->assertIsArray($eta);
        $this->assertArrayHasKey('distance_meters', $eta);
        $this->assertArrayHasKey('eta_minutes', $eta);
        $this->assertArrayHasKey('traffic_factor', $eta);
        $this->assertGreaterThan(0, $eta['eta_minutes']);
    }

    public function test_update_driver_location(): void
    {
        $driver = Driver::factory()->create([
            'current_lat' => 55.7558,
            'current_lon' => 37.6173,
        ]);

        $this->geoService->updateDriverLocation(
            driverId: $driver->id,
            latitude: 55.7600,
            longitude: 37.6200,
            correlationId: 'test-correlation',
        );

        $driver->refresh();
        $this->assertEquals(55.7600, $driver->current_lat);
        $this->assertEquals(37.6200, $driver->current_lon);
    }

    public function test_get_min_price_in_rubles(): void
    {
        $zone = TaxiGeoZone::factory()->create([
            'min_price_kopeki' => 15000,
        ]);

        $this->assertEquals(150.0, $zone->getMinPriceInRubles());
    }

    public function test_get_max_price_in_rubles(): void
    {
        $zone = TaxiGeoZone::factory()->create([
            'max_price_kopeki' => 500000,
        ]);

        $this->assertEquals(5000.0, $zone->getMaxPriceInRubles());
    }
}
