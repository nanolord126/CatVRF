<?php

namespace Tests\Unit\Domains\Taxi\Services;

use Modules\Auto\Services\TaxiService;
use Modules\Auto\Models\TaxiRide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaxiService();
        $this->user = User::factory()->create();
    }

    public function test_create_ride_creates_taxi_ride(): void
    {
        $data = [
            'driver_id' => User::factory()->create()->id,
            'passenger_id' => User::factory()->create()->id,
            'vehicle_class' => 'economy',
            'pickup_lat' => 40.7128,
            'pickup_lng' => -74.0060,
            'dropoff_lat' => 40.7589,
            'dropoff_lng' => -73.9851,
            'distance_km' => 5.5,
            'fare_amount' => 150,
        ];

        $ride = $this->service->createRide($data);
        $this->assertInstanceOf(TaxiRide::class, $ride);
        $this->assertEquals(150, $ride->fare_amount);
        $this->assertDatabaseHas('taxi_rides', ['id' => $ride->id]);
    }

    public function test_complete_ride_updates_status(): void
    {
        $ride = TaxiRide::factory()->create(['status' => 'accepted']);
        $completed = $this->service->completeRide($ride);
        $this->assertEquals('completed', $completed->status);
    }
}
