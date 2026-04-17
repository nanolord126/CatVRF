<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Sports;

use App\Domains\Sports\Http\Controllers\AdaptiveWorkoutController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdaptiveWorkoutApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_generate_adaptive_workout(): void
    {
        $response = $this->postJson('/api/v1/sports/adaptive-workouts/generate', [
            'fitness_level' => 'intermediate',
            'goals' => ['weight_loss', 'strength'],
            'limitations' => ['knee_injury'],
            'sport_type' => 'fitness',
            'weekly_frequency' => 4,
            'session_duration_minutes' => 60,
            'available_equipment' => ['dumbbells', 'bench'],
        ], [
            'X-Correlation-ID' => 'test-correlation-123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'workout_plan',
                'recommendations',
                'version',
            ]);
    }

    public function test_generate_adaptive_workout_validation_error(): void
    {
        $response = $this->postJson('/api/v1/sports/adaptive-workouts/generate', [
            'fitness_level' => 'invalid',
            'goals' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fitness_level', 'goals']);
    }

    public function test_adjust_workout_plan(): void
    {
        $response = $this->postJson("/api/v1/sports/adaptive-workouts/{$this->user->id}/adjust", [
            'too_easy' => true,
            'increase_intensity' => true,
            'focus_areas' => ['upper_body'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'adjustment_count',
                'adjusted_at',
            ]);
    }

    public function test_track_workout_progress(): void
    {
        $response = $this->postJson("/api/v1/sports/adaptive-workouts/{$this->user->id}/progress", [
            'duration_minutes' => 45,
            'exercises_completed' => 8,
            'intensity' => 'high',
            'calories_burned' => 300,
            'heart_rate_avg' => 145,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_sessions',
                'total_minutes',
                'last_updated',
            ]);
    }

    public function test_show_workout_plan(): void
    {
        $response = $this->getJson("/api/v1/sports/adaptive-workouts/{$this->user->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'workout_plan',
            ]);
    }

    public function test_unauthorized_access(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->postJson("/api/v1/sports/adaptive-workouts/{$otherUser->id}/adjust", [
            'too_easy' => false,
        ]);

        $response->assertStatus(403);
    }
}
