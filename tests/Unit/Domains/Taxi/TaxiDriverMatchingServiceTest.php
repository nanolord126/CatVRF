<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Taxi;

use App\Domains\Taxi\DTOs\TaxiDriverMatchingDto;
use App\Domains\Taxi\DTOs\TaxiDriverMatchingResultDto;
use App\Domains\Taxi\Models\TaxiDriver;
use App\Domains\Taxi\Services\TaxiDriverMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaxiDriverMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private readonly TaxiDriverMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiDriverMatchingService::class);
    }

    public function test_match_driver_returns_best_driver(): void
    {
        TaxiDriver::factory()->create([
            'is_online' => true,
            'status' => 'active',
            'current_lat' => 55.75396,
            'current_lon' => 37.62039,
            'rating' => 4.8,
        ]);

        TaxiDriver::factory()->create([
            'is_online' => false,
            'status' => 'active',
            'current_lat' => 55.75396,
            'current_lon' => 37.62039,
            'rating' => 4.9,
        ]);

        $dto = new TaxiDriverMatchingDto(
            rideId: 1,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->matchDriver($dto);

        $this->assertInstanceOf(TaxiDriverMatchingResultDto::class, $result);
        $this->assertNotNull($result->driverId);
        $this->assertGreaterThan(0, $result->distanceKm);
        $this->assertGreaterThan(0, $result->estimatedArrivalMinutes);
    }

    public function test_match_driver_ignores_offline_drivers(): void
    {
        TaxiDriver::factory()->create([
            'is_online' => false,
            'status' => 'active',
            'current_lat' => 55.75396,
            'current_lon' => 37.62039,
            'rating' => 4.9,
        ]);

        $dto = new TaxiDriverMatchingDto(
            rideId: 1,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->matchDriver($dto);

        $this->assertNull($result->driverId);
        $this->assertEquals('no_drivers_available', $result->reason);
    }

    public function test_match_driver_prefers_higher_rating(): void
    {
        TaxiDriver::factory()->create([
            'is_online' => true,
            'status' => 'active',
            'current_lat' => 55.75396,
            'current_lon' => 37.62039,
            'rating' => 4.5,
        ]);

        TaxiDriver::factory()->create([
            'is_online' => true,
            'status' => 'active',
            'current_lat' => 55.75396,
            'current_lon' => 37.62039,
            'rating' => 4.9,
        ]);

        $dto = new TaxiDriverMatchingDto(
            rideId: 1,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->matchDriver($dto);

        $this->assertNotNull($result->driverId);
        $matchedDriver = TaxiDriver::find($result->driverId);
        $this->assertEquals(4.9, $matchedDriver->rating);
    }

    public function test_match_driver_calculates_distance_correctly(): void
    {
        TaxiDriver::factory()->create([
            'is_online' => true,
            'status' => 'active',
            'current_lat' => 55.75396,
            'current_lon' => 37.62039,
            'rating' => 4.8,
        ]);

        $dto = new TaxiDriverMatchingDto(
            rideId: 1,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->matchDriver($dto);

        $this->assertGreaterThanOrEqual(0, $result->distanceKm);
        $this->assertLessThan(50, $result->distanceKm);
    }

    public function test_match_driver_returns_null_when_no_drivers(): void
    {
        $dto = new TaxiDriverMatchingDto(
            rideId: 1,
            pickupLat: 55.75396,
            pickupLon: 37.62039,
            tenantId: 1,
            correlationId: 'test-correlation-123',
        );

        $result = $this->service->matchDriver($dto);

        $this->assertNull($result->driverId);
        $this->assertEquals('no_drivers_available', $result->reason);
    }
}
