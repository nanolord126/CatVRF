<?php declare(strict_types=1);

namespace Tests\Feature\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\CourseModule;
use App\Domains\Education\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use App\Domains\Education\Events\LearningPathGeneratedEvent;

final class LearningPathApiTest extends TestCase
{
    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_generate_learning_path_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/education/learning-paths/generate', [
            'user_id' => $this->user->id,
            'course_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    public function test_generate_learning_path_success(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        CourseModule::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        Event::fake([LearningPathGeneratedEvent::class]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/learning-paths/generate', [
                'user_id' => $this->user->id,
                'course_id' => $course->id,
                'learning_goal' => 'Learn Python programming',
                'current_level' => 'beginner',
                'target_level' => 'intermediate',
                'weekly_hours' => 10,
                'preferred_topics' => ['variables', 'functions'],
                'learning_style' => 'visual',
            ], [
                'X-Correlation-ID' => 'test-correlation-123',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'path_id',
                'modules',
                'estimated_hours',
                'estimated_weeks',
                'difficulty_level',
                'adaptive_adjustments',
                'recommended_resources',
                'completion_probability',
                'milestones',
                'generated_at',
            ])
            ->assertHeader('X-Correlation-ID', 'test-correlation-123');

        Event::assertDispatched(LearningPathGeneratedEvent::class);
    }

    public function test_generate_learning_path_validation_error(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/learning-paths/generate', [
                'user_id' => 999999,
                'course_id' => 999999,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'course_id']);
    }

    public function test_generate_learning_path_invalid_level(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/learning-paths/generate', [
                'user_id' => $this->user->id,
                'course_id' => 1,
                'current_level' => 'invalid_level',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_level']);
    }

    public function test_generate_learning_path_invalid_weekly_hours(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/learning-paths/generate', [
                'user_id' => $this->user->id,
                'course_id' => 1,
                'weekly_hours' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['weekly_hours']);
    }

    public function test_adapt_learning_path_success(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        $enrollment = Enrollment::factory()->create([
            'user_id' => $this->user->id,
            'course_id' => $course->id,
            'tenant_id' => tenant()->id,
            'ai_path' => [
                'path_id' => 'original-path-123',
                'modules' => [],
                'estimated_hours' => 50,
                'estimated_weeks' => 5,
                'difficulty_level' => 'medium',
                'adaptive_adjustments' => [],
                'recommended_resources' => [],
                'completion_probability' => 0.75,
                'milestones' => [],
                'generated_at' => now()->toIso8601String(),
            ],
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/v1/education/learning-paths/adapt/{$enrollment->id}", [
                'progress_data' => [
                    'completed_modules' => 2,
                    'total_modules' => 10,
                    'average_score' => 85,
                    'time_spent_hours' => 20,
                ],
            ], [
                'X-Correlation-ID' => 'adapt-correlation-123',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'path_id',
                'modules',
                'estimated_hours',
                'estimated_weeks',
                'difficulty_level',
                'adaptive_adjustments',
                'recommended_resources',
                'completion_probability',
                'milestones',
                'generated_at',
            ])
            ->assertHeader('X-Correlation-ID', 'adapt-correlation-123');
    }

    public function test_adapt_learning_path_validation_error(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/learning-paths/adapt/999', []);

        $response->assertStatus(404);
    }

    public function test_generate_learning_path_b2b_mode(): void
    {
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        CourseModule::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/v1/education/learning-paths/generate', [
                'user_id' => $this->user->id,
                'course_id' => $course->id,
                'inn' => '1234567890',
                'business_card_id' => 1,
            ], [
                'X-Correlation-ID' => 'b2b-correlation-123',
            ]);

        $response->assertStatus(201);
    }
}
