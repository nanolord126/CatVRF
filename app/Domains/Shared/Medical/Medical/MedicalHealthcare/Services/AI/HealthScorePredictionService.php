<?php declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Services\AI;

use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\HealthScorePredictionDto;
use App\Services\Resilience\CircuitBreaker;
use App\Services\AI\OpenAIClientService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * HealthScorePredictionService - Predicts health score trends
 * 
 * Analyzes current health data to predict future health scores.
 */
final readonly class HealthScorePredictionService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly CircuitBreaker $circuitBreaker,
        private readonly OpenAIClientService $openai,
        private readonly Cache $cache,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly MedicalDataAnonymizerService $anonymizer,
    ) {}

    public function predictHealthScore(int $userId, string $correlationId): HealthScorePredictionDto
    {
        $cacheKey = "healthcare:prediction:{$userId}";
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return HealthScorePredictionDto::fromJson($cached);
        }

        $currentScore = $this->getCurrentHealthScore($userId);
        $medicalHistory = $this->getMedicalHistory($userId);

        $prompt = $this->buildPredictionPrompt($currentScore, $medicalHistory);
        $anonymizedPrompt = $this->anonymizer->anonymizeMedicalText($prompt);

        try {
            $response = $this->circuitBreaker->call(function () use ($anonymizedPrompt) {
                return $this->openai->chat([
                    ['role' => 'system', 'content' => $this->buildSystemPrompt()],
                    ['role' => 'user', 'content' => $anonymizedPrompt],
                ], 0.3, 'json');
            });
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to predict health score. Please try again later.');
        }

        $predictionData = json_decode($response['content'], true);
        $result = HealthScorePredictionDto::fromArray($predictionData);
        $result->correlationId = $correlationId;

        $this->cache->put($cacheKey, json_encode($result->toArray()), self::CACHE_TTL);

        $this->logger->info('health_score_prediction.completed', [
            'user_id' => $userId,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    private function getCurrentHealthScore(int $userId): int
    {
        return $this->db->table('medical_records')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->value('health_score') ?? 75;
    }

    private function getMedicalHistory(int $userId): array
    {
        return $this->db->table('medical_records')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a health analytics AI. Predict health score for the next 30 days based on:
- Current health score
- Medical history
- Recent symptoms

Return JSON:
{
  "current_score": 75,
  "predicted_30_days": 72,
  "trend": "declining",
  "key_factors": [],
  "recommendations": [],
  "risk_areas": [],
  "confidence": 0.8
}
PROMPT;
    }

    private function buildPredictionPrompt(int $currentScore, array $history): string
    {
        return "Current health score: {$currentScore}. Medical history: " . json_encode($history);
    }
}
