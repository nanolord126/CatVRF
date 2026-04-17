<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Sports;

use App\Domains\Sports\Models\Gym;
use App\Domains\Sports\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RealTimeBookingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Gym $gym;
    private Trainer $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->gym = Gym::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'name' => 'Test Gym',
            'address' => 'Test Address',
            'single_visit_price' => 500,
            'monthly_membership_price' => 3000,
            'personal_training_price' => 1500,
            'group_class_price' => 500,
            'max_daily_capacity' => 200,
            'is_active' => true,
        ]);

        $this->trainer = Trainer::create([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'tenant_id' => 1,
            'business_group_id' => null,
            'gym_id' => $this->gym->id,
            'user_id' => User::factory()->create()->id,
            'name' => 'Test Trainer',
            'specialization' => 'fitness',
            'hourly_rate' => 1500,
            'is_active' => true,
        ]);
    }

    public function test_hold_slot_success(): void
    {
        $response = $this->postJson('/api/v1/sports/bookings/hold', [
            'venue_id' => $this->gym->id,
            'trainer_id' => $this->trainer->id,
            'slot_start' => now()->addHours(2)->toIso8601String(),
            'slot_end' => now()->addHours(3)->toIso8601String(),
            'booking_type' => 'personal_training',
            'extended_hold' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'hold_until',
                'hold_id',
            ]);
    }

    public function test_hold_slot_validation_error(): void
    {
        $response = $this->postJson('/api/v1/sports/bookings/hold', [
            'venue_id' => 999,
            'slot_start' => 'invalid-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['venue_id', 'slot_start']);
    }

    public function test_get_available_slots(): void
    {
        $response = $this->getJson("/api/v1/sports/bookings/venues/{$this->gym->id}/slots", [
            'date' => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'slots',
            ]);
    }

    public function test_extend_hold_success(): void
    {
        $this->postJson('/api/v1/sports/bookings/hold', [
            'venue_id' => $this->gym->id,
            'trainer_id' => $this->trainer->id,
            'slot_start' => now()->addHours(2)->toIso8601String(),
            'slot_end' => now()->addHours(3)->toIso8601String(),
            'booking_type' => 'personal_training',
        ]);

        $response = $this->postJson('/api/v1/sports/bookings/extend-hold', [
            'venue_id' => $this->gym->id,
            'trainer_id' => $this->trainer->id,
            'slot_start' => now()->addHours(2)->toIso8601String(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'hold_until',
            ]);
    }

    public function test_release_slot_success(): void
    {
        $this->postJson('/api/v1/sports/bookings/hold', [
            'venue_id' => $this->gym->id,
            'trainer_id' => null,
            'slot_start' => now()->addHours(2)->toIso8601String(),
            'slot_end' => now()->addHours(3)->toIso8601String(),
            'booking_type' => 'gym_access',
        ]);

        $response = $this->postJson('/api/v1/sports/bookings/release', [
            'venue_id' => $this->gym->id,
            'trainer_id' => null,
            'slot_start' => now()->addHours(2)->toIso8601String(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
