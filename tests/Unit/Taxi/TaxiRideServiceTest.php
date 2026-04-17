<?php declare(strict_types=1);

namespace Tests\Unit\Taxi;

use Tests\TestCase;
use Modules\Taxi\Services\TaxiRideService;
use Modules\Taxi\Services\TaxiRideCreateDto;
use Modules\Taxi\Models\TaxiRide;
use Modules\Taxi\Models\TaxiDriver;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\Payment\PaymentService;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

final class TaxiRideServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxiRideService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaxiRideService::class);
    }

    public function test_create_ride_successfully(): void
    {
        $dto = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Kremlin, Moscow',
            dropoffAddress: 'Red Square, Moscow',
            estimatedPriceKopeki: 15000,
            correlationId: 'test-correlation-id',
        );

        $ride = $this->service->createRide($dto);

        $this->assertInstanceOf(TaxiRide::class, $ride);
        $this->assertEquals(TaxiRide::STATUS_REQUESTED, $ride->status);
        $this->assertEquals(15000, $ride->estimated_price_kopeki);
    }

    public function test_create_ride_with_b2b(): void
    {
        $dto = new TaxiRideCreateDto(
            tenantId: 1,
            passengerId: 1,
            pickupLatitude: 55.7558,
            pickupLongitude: 37.6173,
            dropoffLatitude: 55.7520,
            dropoffLongitude: 37.6150,
            pickupAddress: 'Kremlin, Moscow',
            dropoffAddress: 'Red Square, Moscow',
            estimatedPriceKopeki: 15000,
            correlationId: 'test-correlation-id',
            inn: '7707083893',
            businessCardId: 'BC123456',
        );

        $ride = $this->service->createRide($dto);

        $this->assertTrue($ride->metadata['is_b2b'] ?? false);
        $this->assertEquals('7707083893', $ride->metadata['inn']);
    }

    public function test_match_driver_successfully(): void
    {
        $ride = TaxiRide::factory()->create(['status' => TaxiRide::STATUS_REQUESTED]);
        TaxiDriver::factory()->create(['status' => TaxiDriver::STATUS_AVAILABLE, 'is_active' => true]);

        $updatedRide = $this->service->matchDriver($ride->id, 'test-correlation-id');

        $this->assertEquals(TaxiRide::STATUS_ACCEPTED, $updatedRide->status);
        $this->assertNotNull($updatedRide->driver_id);
    }

    public function test_start_ride_successfully(): void
    {
        $driver = TaxiDriver::factory()->create();
        $ride = TaxiRide::factory()->create([
            'status' => TaxiRide::STATUS_ACCEPTED,
            'driver_id' => $driver->id,
        ]);

        $updatedRide = $this->service->startRide($ride->id, 'test-correlation-id');

        $this->assertEquals(TaxiRide::STATUS_STARTED, $updatedRide->status);
        $this->assertNotNull($updatedRide->started_at);
    }

    public function test_complete_ride_successfully(): void
    {
        $driver = TaxiDriver::factory()->create();
        $ride = TaxiRide::factory()->create([
            'status' => TaxiRide::STATUS_STARTED,
            'driver_id' => $driver->id,
            'final_price_kopeki' => 15000,
        ]);

        $updatedRide = $this->service->completeRide($ride->id, 'test-correlation-id');

        $this->assertEquals(TaxiRide::STATUS_COMPLETED, $updatedRide->status);
        $this->assertNotNull($updatedRide->completed_at);
    }

    public function test_cancel_ride_with_fee(): void
    {
        $driver = TaxiDriver::factory()->create();
        $ride = TaxiRide::factory()->create([
            'status' => TaxiRide::STATUS_STARTED,
            'driver_id' => $driver->id,
            'final_price_kopeki' => 10000,
        ]);

        $updatedRide = $this->service->cancelRide($ride->id, 'Test reason', 'test-correlation-id');

        $this->assertEquals(TaxiRide::STATUS_CANCELLED, $updatedRide->status);
        $this->assertEquals('Test reason', $updatedRide->cancellation_reason);
    }

    public function test_submit_rating_successfully(): void
    {
        $driver = TaxiDriver::factory()->create(['rating' => 4.5, 'ride_count' => 10]);
        $ride = TaxiRide::factory()->create([
            'status' => TaxiRide::STATUS_COMPLETED,
            'driver_id' => $driver->id,
        ]);

        $updatedRide = $this->service->submitRating($ride->id, 5, 'passenger', 'test-correlation-id');

        $this->assertEquals(5, $updatedRide->driver_rating);
    }

    public function test_update_driver_location_successfully(): void
    {
        $driver = TaxiDriver::factory()->create([
            'current_latitude' => 55.7558,
            'current_longitude' => 37.6173,
        ]);

        $this->service->updateDriverLocation($driver->id, 55.7560, 37.6175, 'test-correlation-id');

        $driver->refresh();
        $this->assertEquals(55.7560, $driver->current_latitude);
        $this->assertEquals(37.6175, $driver->current_longitude);
    }
}
