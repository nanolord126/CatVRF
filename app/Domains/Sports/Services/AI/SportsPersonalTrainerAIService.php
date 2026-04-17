<?php

declare(strict_types=1);

namespace App\Domains\Sports\Services\AI;

use App\Domains\Sports\DTOs\AdaptiveWorkoutPlanDto;
use App\Domains\Sports\Events\AdaptiveWorkoutGeneratedEvent;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\RecommendationService;
use App\Services\AI\OpenAIClientService;
use App\Services\Resilience\CircuitBreaker;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

final readonly class SportsPersonalTrainerAIService
{
    private const CACHE_TTL = 3600;
    private const EMBEDDING_DIMENSION = 1536;
    private const WORKOUT_PLAN_VERSION = '2.0';

    public function __construct(
        private FraudControlService $fraud,
        private AuditService $audit,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private RecommendationService $recommendation,
        private OpenAIClientService $openai,
        private CircuitBreaker $circuitBreaker,
        private DatabaseManager $db,
        private Cache $cache,
        private LoggerInterface $logger,
        private RedisConnection $redis,
    ) {}

    public function generateAdaptiveWorkoutPlan(AdaptiveWorkoutPlanDto $dto): array
    {
        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'adaptive_workout_generation',
            amount: 0,
            correlationId: $dto->correlationId,
        );

        $cacheKey = "sports:adaptive_workout:{$dto->userId}:" . md5(json_encode($dto->toArray()));

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $userHistory = $this->getUserWorkoutHistory($dto->userId);
        $tasteProfile = $this->tasteAnalyzer->getProfile($dto->userId);

        $systemPrompt = $this->buildWorkoutSystemPrompt();
        $userPrompt = $this->buildWorkoutUserPrompt($dto, $userHistory, $tasteProfile);

        $anonymizedPrompt = $this->anonymizeUserData($userPrompt);

        try {
            $response = $this->circuitBreaker->call(function () use ($systemPrompt, $anonymizedPrompt) {
                return $this->openai->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $anonymizedPrompt],
                ], 0.3, 'json');
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                throw new \RuntimeException('AI service temporarily unavailable. Please try again later.');
            }
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI API call failed for workout plan', [
                'error' => $e->getMessage(),
                'user_id' => $dto->userId,
                'correlation_id' => $dto->correlationId,
            ]);
            throw new \RuntimeException('Failed to generate workout plan. Please try again later.');
        }

        $workoutData = json_decode($response['content'], true);
        if ($workoutData === null) {
            throw new \RuntimeException('Invalid AI response format.');
        }

        $embedding = $this->generateEmbedding(json_encode($workoutData));

        return $this->db->transaction(function () use ($dto, $cacheKey, $workoutData, $embedding, $response) {
            $workoutPlan = $this->enrichWorkoutPlan($workoutData, $dto, $embedding);

            $this->saveWorkoutPlan($dto->userId, $workoutPlan, $dto->correlationId);

            $recommendations = $this->recommendation->getForVertical(
                'sports',
                ['fitness_level' => $dto->fitnessLevel, 'sport_type' => $dto->sportType],
                $dto->userId
            );

            $result = [
                'success' => true,
                'workout_plan' => $workoutPlan,
                'recommendations' => $recommendations,
                'version' => self::WORKOUT_PLAN_VERSION,
                'generated_at' => now()->toIso8601String(),
                'correlation_id' => $dto->correlationId,
            ];

            event(new AdaptiveWorkoutGeneratedEvent(
                userId: $dto->userId,
                dto: $dto,
                workoutPlan: $workoutPlan,
                correlationId: $dto->correlationId,
            ));

            $this->audit->log(
                action: 'adaptive_workout_generated',
                entityType: 'sports_workout_plan',
                entityId: $dto->userId,
                metadata: [
                    'fitness_level' => $dto->fitnessLevel,
                    'sport_type' => $dto->sportType,
                    'weekly_frequency' => $dto->weeklyFrequency,
                    'correlation_id' => $dto->correlationId,
                ]
            );

            $this->logger->info('Adaptive workout plan generated', [
                'user_id' => $dto->userId,
                'fitness_level' => $dto->fitnessLevel,
                'sport_type' => $dto->sportType,
                'correlation_id' => $dto->correlationId,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ]);

            $this->cache->put($cacheKey, json_encode($result), self::CACHE_TTL);

            return $result;
        });
    }

    public function adjustWorkoutPlan(int $userId, array $feedback, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'workout_adjustment',
            amount: 0,
            correlationId: $correlationId,
        );

        $currentPlan = $this->getCurrentWorkoutPlan($userId);
        if ($currentPlan === null) {
            throw new \RuntimeException('No active workout plan found.');
        }

        $systemPrompt = 'You are an adaptive AI personal trainer. Adjust the workout plan based on user feedback.';
        $userPrompt = $this->buildAdjustmentPrompt($currentPlan, $feedback);

        try {
            $response = $this->circuitBreaker->call(function () use ($systemPrompt, $userPrompt) {
                return $this->openai->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ], 0.3, 'json');
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                throw new \RuntimeException('AI service temporarily unavailable.');
            }
            throw $e;
        }

        $adjustedData = json_decode($response['content'], true);
        if ($adjustedData === null) {
            throw new \RuntimeException('Invalid AI response format.');
        }

        return $this->db->transaction(function () use ($userId, $currentPlan, $adjustedData, $correlationId) {
            $adjustedPlan = array_merge($currentPlan, $adjustedData);
            $adjustedPlan['adjusted_at'] = now()->toIso8601String();
            $adjustedPlan['adjustment_count'] = ($currentPlan['adjustment_count'] ?? 0) + 1;

            $this->saveWorkoutPlan($userId, $adjustedPlan, $correlationId);

            $this->audit->log(
                action: 'workout_plan_adjusted',
                entityType: 'sports_workout_plan',
                entityId: $userId,
                metadata: [
                    'adjustment_count' => $adjustedPlan['adjustment_count'],
                    'correlation_id' => $correlationId,
                ]
            );

            return $adjustedPlan;
        });
    }

    public function trackWorkoutProgress(int $userId, array $sessionData, string $correlationId): array
    {
        $this->fraud->check(
            userId: $userId,
            operationType: 'workout_progress_tracking',
            amount: 0,
            correlationId: $correlationId,
        );

        return $this->db->transaction(function () use ($userId, $sessionData, $correlationId) {
            $progressKey = "sports:progress:{$userId}";
            $currentProgress = json_decode($this->redis->get($progressKey) ?? '{}', true);

            $updatedProgress = array_merge($currentProgress, [
                'last_session' => $sessionData,
                'total_sessions' => ($currentProgress['total_sessions'] ?? 0) + 1,
                'total_minutes' => ($currentProgress['total_minutes'] ?? 0) + ($sessionData['duration_minutes'] ?? 0),
                'last_updated' => now()->toIso8601String(),
            ]);

            $this->redis->setex($progressKey, 2592000, json_encode($updatedProgress));

            $this->audit->log(
                action: 'workout_progress_tracked',
                entityType: 'sports_workout_progress',
                entityId: $userId,
                metadata: [
                    'session_data' => $sessionData,
                    'correlation_id' => $correlationId,
                ]
            );

            return $updatedProgress;
        });
    }

    private function buildWorkoutSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an elite AI personal trainer for CatVRF Sports platform. Your task:

1. Generate personalized adaptive workout plans based on fitness level, goals, limitations
2. Create progressive overload schedules with proper rest periods
3. Recommend exercises with sets, reps, tempo, rest times
4. Include warm-up, main workout, cool-down phases
5. Adapt to available equipment and time constraints
6. Provide alternative exercises for limitations
7. Calculate intensity zones (heart rate, RPE)
8. Include recovery and nutrition recommendations

CRITICAL RULES:
- Always consider safety first
- Provide regressions and progressions
- Include deload weeks every 4-6 weeks
- Account for sport-specific demands
- Consider individual recovery capacity

Response in JSON format:
{
  "weekly_schedule": [
    {
      "day": "Monday",
      "focus": "strength",
      "exercises": [
        {
          "name": "string",
          "sets": int,
          "reps": "string",
          "tempo": "string",
          "rest_seconds": int,
          "intensity_zone": "string",
          "alternatives": ["string"]
        }
      ],
      "warm_up": ["string"],
      "cool_down": ["string"],
      "estimated_duration_minutes": int
    }
  ],
  "progression_strategy": {
    "type": "string",
    "frequency": "string",
    "deload_frequency": "string"
  },
  "nutrition_recommendations": {
    "pre_workout": "string",
    "post_workout": "string",
    "daily_calories": int,
    "macro_split": {
      "protein_percent": int,
      "carbs_percent": int,
      "fat_percent": int
    }
  },
  "recovery_recommendations": ["string"],
  "adaptation_markers": ["string"],
  "confidence": 0.0-1.0
}
PROMPT;
    }

    private function buildWorkoutUserPrompt(AdaptiveWorkoutPlanDto $dto, string $userHistory, array $tasteProfile): string
    {
        return <<<PROMPT
User Profile:
- Fitness Level: {$dto->fitnessLevel}
- Goals: {$this->formatArray($dto->goals)}
- Limitations: {$this->formatArray($dto->limitations)}
- Sport Type: {$dto->sportType}
- Weekly Frequency: {$dto->weeklyFrequency} days
- Session Duration: {$dto->sessionDurationMinutes} minutes
- Available Equipment: {$this->formatArray($dto->availableEquipment)}

Workout History:
{$userHistory}

User Preferences (from ML profile):
{$this->formatArray($tasteProfile)}

Generate a personalized adaptive workout plan following the JSON format.
PROMPT;
    }

    private function buildAdjustmentPrompt(array $currentPlan, array $feedback): string
    {
        return <<<PROMPT
Current Workout Plan:
{$this->formatArray($currentPlan)}

User Feedback:
{$this->formatArray($feedback)}

Adjust the workout plan based on the feedback. Maintain the structure and improve based on user input.
PROMPT;
    }

    private function getUserWorkoutHistory(int $userId): string
    {
        $history = $this->redis->get("sports:progress:{$userId}");
        
        if ($history === null) {
            return 'No workout history available.';
        }

        return $history;
    }

    public function getCurrentWorkoutPlan(int $userId): ?array
    {
        $planKey = "sports:current_plan:{$userId}";
        $plan = $this->redis->get($planKey);
        
        return $plan !== null ? json_decode($plan, true) : null;
    }

    private function enrichWorkoutPlan(array $workoutData, AdaptiveWorkoutPlanDto $dto, array $embedding): array
    {
        return array_merge($workoutData, [
            'user_id' => $dto->userId,
            'tenant_id' => $dto->tenantId,
            'business_group_id' => $dto->businessGroupId,
            'embedding' => $embedding,
            'generated_at' => now()->toIso8601String(),
            'version' => self::WORKOUT_PLAN_VERSION,
            'adjustment_count' => 0,
            'is_active' => true,
        ]);
    }

    private function saveWorkoutPlan(int $userId, array $plan, string $correlationId): void
    {
        $this->db->table('sports_adaptive_workout_plans')->updateOrInsert(
            [
                'user_id' => $userId,
                'is_active' => true,
            ],
            [
                'plan_data' => json_encode($plan),
                'embedding' => json_encode($plan['embedding'] ?? []),
                'correlation_id' => $correlationId,
                'updated_at' => now(),
            ]
        );

        $this->redis->setex(
            "sports:current_plan:{$userId}",
            2592000,
            json_encode($plan)
        );
    }

    private function generateEmbedding(string $text): array
    {
        if ($this->openai->isEnabled()) {
            try {
                return $this->openai->generateEmbedding($text);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to generate real embedding, falling back to deterministic', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $hash = md5($text);
        $embedding = [];

        for ($i = 0; $i < self::EMBEDDING_DIMENSION; $i++) {
            $embedding[$i] = (sin($hash + $i * 0.1) + 1) / 2;
        }

        return $embedding;
    }

    private function anonymizeUserData(string $data): string
    {
        $patterns = [
            '/\b[A-ZА-Я][a-zа-я]+\s+[A-ZА-Я][a-zа-я]+\b/' => '[ПОЛЬЗОВАТЕЛЬ]',
            '/\b\d{11}\b/' => '[ТЕЛЕФОН]',
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/' => '[EMAIL]',
            '/\b\d{2}\.\d{2}\.\d{4}\b/' => '[ДАТА]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $data);
    }

    private function formatArray(array $data): string
    {
        return empty($data) ? 'None' : implode(', ', $data);
    }
}
