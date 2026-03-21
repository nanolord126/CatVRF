<?php declare(strict_types=1);

namespace Tests\Feature\Auto;

use App\Domains\Auto\Models\TaxiRide;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class RideBookingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_passenger_can_request_ride(): void
    {
        $passenger = User::factory()->create([
            'email' => 'passenger@example.com',
            'tenant_id' => 1,
        ]);

        $response = $this->actingAs($passenger)
            ->postJson('/api/rides', [
                'pickup_location' => [
                    'latitude' => 55.7558,
                    'longitude' => 37.6173,
                ],
                'dropoff_location' => [
                    'latitude' => 55.7615,
                    'longitude' => 37.6299,
                ],
                'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'status',
                'pickup_location',
                'dropoff_location',
                'estimated_price',
                'correlation_id',
            ]);

        $this->assertDatabaseHas('taxi_rides', [
            'status' => 'pending',
        ]);
    }

    public function test_ride_pricing_includes_surge_multiplier(): void
    {
        $passenger = User::factory()->create(['tenant_id' => 1]);

        $response = $this->actingAs($passenger)
            ->postJson('/api/rides/estimate', [
                'pickup_location' => [
                    'latitude' => 55.7558,
                    'longitude' => 37.6173,
                ],
                'dropoff_location' => [
                    'latitude' => 55.7615,
                    'longitude' => 37.6299,
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'base_price',
                'surge_multiplier',
                'estimated_price',
                'duration_minutes',
                'distance_km',
            ]);
    }

    public function test_driver_can_accept_ride(): void
    {
        $ride = TaxiRide::factory()->create([
            'status' => 'pending',
        ]);

        $driver = User::factory()->create([
            'email' => 'driver@example.com',
            'tenant_id' => $ride->tenant_id,
        ]);

        $response = $this->actingAs($driver)
            ->postJson("/api/rides/{$ride->id}/accept");

        $response->assertStatus(200);

        $this->assertEquals('accepted', $ride->fresh()->status);
    }

    public function test_passenger_cannot_book_with_insufficient_balance(): void
    {
        $passenger = User::factory()->create([
            'tenant_id' => 1,
        ]);

        // Mock wallet with zero balance
        $wallet = $passenger->wallet()->firstOrCreate(
            ['user_id' => $passenger->id],
            ['current_balance' => 0, 'tenant_id' => 1]
        );

        $response = $this->actingAs($passenger)
            ->postJson('/api/rides', [
                'pickup_location' => ['latitude' => 55.7558, 'longitude' => 37.6173],
                'dropoff_location' => ['latitude' => 55.7615, 'longitude' => 37.6299],
            ]);

        $response->assertStatus(402); // Payment Required
    }
}
