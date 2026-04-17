<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Sports;

use App\Domains\Sports\Models\Gym;
use App\Domains\Sports\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LiveStreamApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $trainerUser;
    private Gym $gym;
    private Trainer $trainer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->trainerUser = User::factory()->create();
        $this->trainerUser->assignRole('trainer');

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
            'user_id' => $this->trainerUser->id,
            'name' => 'Test Trainer',
            'specialization' => 'fitness',
            'hourly_rate' => 1500,
            'is_active' => true,
        ]);
    }

    public function test_create_live_stream(): void
    {
        $response = $this->postJson('/api/v1/sports/live-streams/create', [
            'trainer_id' => $this->trainer->id,
            'session_title' => 'Morning Workout',
            'session_description' => 'Intense morning workout session',
            'scheduled_start' => now()->addHours(2)->toIso8601String(),
            'scheduled_end' => now()->addHours(3)->toIso8601String(),
            'stream_type' => 'group',
            'max_participants' => 50,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stream_id',
                'room_name',
                'status',
            ]);
    }

    public function test_create_live_stream_validation_error(): void
    {
        $response = $this->postJson('/api/v1/sports/live-streams/create', [
            'trainer_id' => 999,
            'session_title' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['trainer_id', 'session_title']);
    }

    public function test_get_active_streams(): void
    {
        $response = $this->getJson('/api/v1/sports/live-streams/active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'streams',
            ]);
    }

    public function test_get_active_streams_with_venue_filter(): void
    {
        $response = $this->getJson('/api/v1/sports/live-streams/active', [
            'venue_id' => $this->gym->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'streams',
            ]);
    }

    public function test_unauthorized_start_stream(): void
    {
        $response = $this->postJson('/api/v1/sports/live-streams/1/start');

        $response->assertStatus(403);
    }
}
