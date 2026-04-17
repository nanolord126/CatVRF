<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Sports;

use App\Domains\Sports\DTOs\AdaptiveWorkoutPlanDto;
use App\Domains\Sports\Services\AI\SportsPersonalTrainerAIService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\AI\OpenAIClientService;
use App\Services\Resilience\CircuitBreaker;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SportsPersonalTrainerAIServiceTest extends TestCase
{
    use RefreshDatabase;

    private SportsPersonalTrainerAIService $service;
    private FraudControlService $fraud;
    private AuditService $audit;
    private UserTasteAnalyzerService $tasteAnalyzer;
    private RecommendationService $recommendation;
    private OpenAIClientService $openai;
    private CircuitBreaker $circuitBreaker;
    private DatabaseManager $db;
    private Cache $cache;
    private RedisConnection $redis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraud = $this->createMock(FraudControlService::class);
        $this->audit = $this->createMock(AuditService::class);
        $this->tasteAnalyzer = $this->createMock(UserTasteAnalyzerService::class);
        $this->recommendation = $this->createMock(RecommendationService::class);
        $this->openai = $this->createMock(OpenAIClientService::class);
        $this->circuitBreaker = $this->createMock(CircuitBreaker::class);
        $this->db = $this->app->make(DatabaseManager::class);
        $this->cache = $this->app->make(Cache::class);
        $this->redis = $this->app->make('redis');

        $this->service = new SportsPersonalTrainerAIService(
            fraud: $this->fraud,
            audit: $this->audit,
            tasteAnalyzer: $this->tasteAnalyzer,
            recommendation: $this->recommendation,
            openai: $this->openai,
            circuitBreaker: $this->circuitBreaker,
            db: $this->db,
            cache: $this->cache,
            logger: $this->app->make('log'),
            redis: $this->redis,
        );
    }

    public function test_generate_adaptive_workout_plan_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('adaptive_workout_generation'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->with(1)
            ->willReturn(new \App\Services\ML\DTOs\UserTasteProfileDto(
                userId: 1,
                preferences: [],
                behaviorScore: 0.5,
            ));

        $this->circuitBreaker->expects($this->once())
            ->method('call')
            ->willReturn([
                'content' => json_encode([
                    'weekly_schedule' => [],
                    'progression_strategy' => [],
                    'nutrition_recommendations' => [],
                    'recovery_recommendations' => [],
                    'adaptation_markers' => [],
                    'confidence' => 0.85,
                ]),
                'usage' => ['total_tokens' => 500],
            ]);

        $this->openai->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->recommendation->expects($this->once())
            ->method('getForVertical')
            ->willReturn([]);

        $dto = new AdaptiveWorkoutPlanDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            correlationId: 'test-correlation-id',
            fitnessLevel: 'intermediate',
            goals: ['weight_loss', 'strength'],
            limitations: [],
            sportType: 'fitness',
            weeklyFrequency: 4,
            sessionDurationMinutes: 60,
            availableEquipment: ['dumbbells', 'bench'],
        );

        $result = $this->service->generateAdaptiveWorkoutPlan($dto);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('workout_plan', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertEquals('2.0', $result['version']);
    }

    public function test_adjust_workout_plan_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('workout_adjustment'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $this->redis->setex(
            "sports:current_plan:1",
            2592000,
            json_encode([
                'weekly_schedule' => [],
                'adjustment_count' => 0,
            ])
        );

        $this->circuitBreaker->expects($this->once())
            ->method('call')
            ->willReturn([
                'content' => json_encode([
                    'weekly_schedule' => [],
                    'intensity_adjustment' => 0.1,
                ]),
                'usage' => ['total_tokens' => 300],
            ]);

        $feedback = ['too_easy' => true, 'increase_intensity' => true];
        $result = $this->service->adjustWorkoutPlan(1, $feedback, 'test-correlation-id');

        $this->assertArrayHasKey('adjustment_count', $result);
        $this->assertEquals(1, $result['adjustment_count']);
        $this->assertArrayHasKey('adjusted_at', $result);

        $this->redis->del("sports:current_plan:1");
    }

    public function test_track_workout_progress_success(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->with(
                $this->equalTo(1),
                $this->equalTo('workout_progress_tracking'),
                $this->equalTo(0),
                $this->equalTo('test-correlation-id')
            );

        $sessionData = [
            'duration_minutes' => 45,
            'exercises_completed' => 8,
            'intensity' => 'high',
        ];

        $result = $this->service->trackWorkoutProgress(1, $sessionData, 'test-correlation-id');

        $this->assertArrayHasKey('total_sessions', $result);
        $this->assertEquals(1, $result['total_sessions']);
        $this->assertArrayHasKey('total_minutes', $result);
        $this->assertEquals(45, $result['total_minutes']);
        $this->assertArrayHasKey('last_updated', $result);

        $this->redis->del("sports:progress:1");
    }

    public function test_generate_adaptive_workout_plan_fraud_check_failure(): void
    {
        $this->fraud->expects($this->once())
            ->method('check')
            ->willThrowException(new \App\Exceptions\FraudBlockedException('Fraud detected'));

        $dto = new AdaptiveWorkoutPlanDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            correlationId: 'test-correlation-id',
            fitnessLevel: 'intermediate',
            goals: ['weight_loss'],
            limitations: [],
            sportType: 'fitness',
            weeklyFrequency: 3,
            sessionDurationMinutes: 60,
            availableEquipment: [],
        );

        $this->expectException(\App\Exceptions\FraudBlockedException::class);
        $this->service->generateAdaptiveWorkoutPlan($dto);
    }

    public function test_generate_adaptive_workout_plan_circuit_breaker_open(): void
    {
        $this->fraud->expects($this->once())
            ->method('check');

        $this->tasteAnalyzer->expects($this->once())
            ->method('getProfile')
            ->willReturn(new \App\Services\ML\DTOs\UserTasteProfileDto(
                userId: 1,
                preferences: [],
                behaviorScore: 0.5,
            ));

        $this->circuitBreaker->expects($this->once())
            ->method('call')
            ->willThrowException(new \RuntimeException('Circuit breaker is open'));

        $dto = new AdaptiveWorkoutPlanDto(
            userId: 1,
            tenantId: 1,
            businessGroupId: null,
            correlationId: 'test-correlation-id',
            fitnessLevel: 'intermediate',
            goals: ['weight_loss'],
            limitations: [],
            sportType: 'fitness',
            weeklyFrequency: 3,
            sessionDurationMinutes: 60,
            availableEquipment: [],
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('AI service temporarily unavailable');
        $this->service->generateAdaptiveWorkoutPlan($dto);
    }
}
