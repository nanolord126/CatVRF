<?php

namespace Tests\Feature\Domains\Taxi;

use App\Models\Domains\Taxi\TaxiRide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxiRideControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $taxiRide;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->taxiRide = TaxiRide::factory()->create(['tenant_id' => tenant()->id]);
    }

    public function test_index_returns_taxi_rides(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/taxi');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_show_returns_taxi_ride(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/taxi/{$this->taxiRide->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->taxiRide->id);
    }

    public function test_store_creates_taxi_ride(): void
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

        $response = $this->actingAs($this->user)->postJson('/api/taxi', $data);
        $response->assertStatus(201);
        $this->assertDatabaseHas('taxi_rides', ['fare_amount' => 150]);
    }

    public function test_update_modifies_taxi_ride(): void
    {
        $data = ['status' => 'completed'];
        $response = $this->actingAs($this->user)->putJson("/api/taxi/{$this->taxiRide->id}", $data);
        $response->assertStatus(200);
        $this->assertDatabaseHas('taxi_rides', ['id' => $this->taxiRide->id, 'status' => 'completed']);
    }

    public function test_destroy_deletes_taxi_ride(): void
    {
        $response = $this->actingAs($this->user)->deleteJson("/api/taxi/{$this->taxiRide->id}");
        $response->assertStatus(204);
        $this->assertDatabaseMissing('taxi_rides', ['id' => $this->taxiRide->id]);
    }
}
