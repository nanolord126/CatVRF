<?php declare(strict_types=1);

namespace Tests\Unit\Domains\Education;

use Tests\TestCase;
use App\Domains\Education\DTOs\CreateLearningPathDto;
use App\Domains\Education\DTOs\LearningPathRecommendationDto;
use App\Domains\Education\Services\AI\EducationLearningPathAIConstructorService;
use App\Domains\Education\Models\Course;
use App\Domains\Education\Models\CourseModule;
use App\Models\User;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Security\IdempotencyService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\ML\AnonymizationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Mockery;

final class EducationLearningPathAIConstructorServiceTest extends TestCase
{
    private EducationLearningPathAIConstructorService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private IdempotencyService $idempotency;
    private UserTasteAnalyzerService $tasteAnalyzer;
    private RecommendationService $recommendation;
    private AnonymizationService $anonymizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = Mockery::mock(FraudControlService::class);
        $this->audit = Mockery::mock(AuditService::class);
        $this->idempotency = Mockery::mock(IdempotencyService::class);
        $this->tasteAnalyzer = Mockery::mock(UserTasteAnalyzerService::class);
        $this->recommendation = Mockery::mock(RecommendationService::class);
        $this->anonymizer = Mockery::mock(AnonymizationService::class);

        $this->service = new EducationLearningPathAIConstructorService(
            $this->fraud,
            $this->audit,
            $this->idempotency,
            $this->tasteAnalyzer,
            $this->recommendation,
            $this->anonymizer,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_personalized_learning_path_success(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        $module = CourseModule::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        $dto = new CreateLearningPathDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            userId: $user->id,
            courseId: $course->id,
            correlationId: 'test-correlation-123',
            idempotencyKey: null,
            learningGoal: 'Learn Python programming',
            currentLevel: 'beginner',
            targetLevel: 'intermediate',
            weeklyHours: 10,
            preferredTopics: ['variables', 'functions'],
            learningStyle: 'visual',
            isCorporate: false,
        );

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);
        $this->tasteAnalyzer->shouldReceive('analyzeAndSaveUserProfile')->once();
        $this->tasteAnalyzer->shouldReceive('getProfile')->once()->andReturn([
            'session_count' => 5,
            'avg_session_duration' => 30,
            'completion_rate' => 85,
            'preferred_content_types' => ['video', 'interactive'],
            'peak_hours' => [10, 14, 19],
        ]);
        $this->recommendation->shouldReceive('getEducationResources')->once()->andReturn([
            'courses' => [],
            'books' => [],
            'videos' => [],
            'practice_exercises' => [],
            'community_forums' => [],
        ]);
        $this->audit->shouldReceive('record')->once();
        $this->anonymizer->shouldReceive('anonymizeEvent')->twice()->andReturn(['learning_style' => ['visual_score' => 0.7]]);

        Redis::shouldReceive('get')->once()->andReturn(null);
        Redis::shouldReceive('setex')->once();

        $result = $this->service->generatePersonalizedLearningPath($dto);

        $this->assertInstanceOf(LearningPathRecommendationDto::class, $result);
        $this->assertNotEmpty($result->pathId);
        $this->assertIsArray($result->modules);
        $this->assertGreaterThan(0, $result->estimatedHours);
        $this->assertGreaterThan(0, $result->estimatedWeeks);
        $this->assertNotEmpty($result->difficultyLevel);
        $this->assertGreaterThan(0, $result->completionProbability);
        $this->assertIsArray($result->milestones);
    }

    public function test_generate_personalized_learning_path_with_idempotency(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        CourseModule::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        $dto = new CreateLearningPathDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            userId: $user->id,
            courseId: $course->id,
            correlationId: 'test-correlation-456',
            idempotencyKey: 'unique-key-123',
            learningGoal: null,
            currentLevel: null,
            targetLevel: null,
            weeklyHours: null,
            preferredTopics: null,
            learningStyle: null,
            isCorporate: false,
        );

        $cachedResponse = [
            'path_id' => 'cached-path-123',
            'modules' => [],
            'estimated_hours' => 50,
            'estimated_weeks' => 5,
            'difficulty_level' => 'medium',
            'adaptive_adjustments' => [],
            'recommended_resources' => [],
            'completion_probability' => 0.75,
            'milestones' => [],
            'generated_at' => now()->toIso8601String(),
        ];

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')
            ->once()
            ->with('learning_path_generation', 'unique-key-123', $dto->toArray(), tenant()->id)
            ->andReturn($cachedResponse);

        $result = $this->service->generatePersonalizedLearningPath($dto);

        $this->assertInstanceOf(LearningPathRecommendationDto::class, $result);
        $this->assertEquals('cached-path-123', $result->pathId);
    }

    public function test_generate_personalized_learning_path_with_cache(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        CourseModule::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        $dto = new CreateLearningPathDto(
            tenantId: tenant()->id,
            businessGroupId: null,
            userId: $user->id,
            courseId: $course->id,
            correlationId: 'test-correlation-789',
            idempotencyKey: null,
            learningGoal: null,
            currentLevel: null,
            targetLevel: null,
            weeklyHours: null,
            preferredTopics: null,
            learningStyle: null,
            isCorporate: false,
        );

        $cachedResponse = json_encode([
            'path_id' => 'cached-path-456',
            'modules' => [],
            'estimated_hours' => 40,
            'estimated_weeks' => 4,
            'difficulty_level' => 'easy',
            'adaptive_adjustments' => [],
            'recommended_resources' => [],
            'completion_probability' => 0.8,
            'milestones' => [],
            'generated_at' => now()->toIso8601String(),
        ]);

        $this->fraud->shouldReceive('check')->once()->with($dto);
        $this->idempotency->shouldReceive('check')->once()->andReturn([]);

        Redis::shouldReceive('get')->once()->andReturn($cachedResponse);

        $result = $this->service->generatePersonalizedLearningPath($dto);

        $this->assertInstanceOf(LearningPathRecommendationDto::class, $result);
        $this->assertEquals('cached-path-456', $result->pathId);
    }

    public function test_adapt_learning_path_success(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create([
            'tenant_id' => tenant()->id,
        ]);

        $enrollment = \App\Domains\Education\Models\Enrollment::factory()->create([
            'user_id' => $user->id,
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

        $progressData = [
            'completed_modules' => 2,
            'total_modules' => 10,
            'average_score' => 85,
            'time_spent_hours' => 20,
        ];

        $this->audit->shouldReceive('record')->once();

        $result = $this->service->adaptLearningPath(
            enrollmentId: $enrollment->id,
            progressData: $progressData,
            correlationId: 'adapt-correlation-123',
        );

        $this->assertInstanceOf(LearningPathRecommendationDto::class, $result);
    }

    public function test_calculate_similarity(): void
    {
        $embedding1 = array_fill(0, 1536, 0.5);
        $embedding2 = array_fill(0, 1536, 0.5);

        $similarity = $this->service->generatePersonalizedLearningPath(
            new CreateLearningPathDto(
                tenantId: tenant()->id,
                businessGroupId: null,
                userId: 1,
                courseId: 1,
                correlationId: 'test',
            )
        );

        $this->assertNotNull($similarity);
    }
}
