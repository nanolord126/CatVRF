<?php declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Services\AI;

use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\AIDiagnosticRequestDto;
use App\Domains\Shared\Medical\MedicalHealthcare\DTOs\AIDiagnosticResultDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\Resilience\CircuitBreaker;
use App\Services\AI\OpenAIClientService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Str;

/**
 * AIDiagnosticCoreService - Core AI diagnosis logic
 * 
 * Handles AI-powered symptom analysis and diagnosis generation.
 * All medical data is anonymized before external AI calls (152-ФЗ compliance).
 */
final readonly class AIDiagnosticCoreService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly FraudControlService $fraud,
        private readonly AuditService $audit,
        private readonly CircuitBreaker $circuitBreaker,
        private readonly OpenAIClientService $openai,
        private readonly Cache $cache,
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly MedicalDataAnonymizerService $anonymizer,
    ) {}

    public function analyzeSymptoms(AIDiagnosticRequestDto $dto): AIDiagnosticResultDto
    {
        $correlationId = $dto->correlationId;

        $this->fraud->check(
            userId: $dto->userId,
            operationType: 'ai_diagnosis',
            amount: 0,
            correlationId: $correlationId,
        );

        $cacheKey = "healthcare:diagnosis:{$dto->userId}:" . md5(json_encode($dto->symptoms));
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return AIDiagnosticResultDto::fromJson($cached);
        }

        $symptomsText = implode(', ', $dto->symptoms);
        $patientHistory = $this->getPatientHistory($dto->userId);
        $systemPrompt = $this->buildDiagnosticSystemPrompt();
        $userPrompt = $this->buildDiagnosticUserPrompt($symptomsText, $patientHistory, $dto->additionalContext);

        // Anonymize medical data before external AI call (152-ФZ compliance)
        $anonymizedUserPrompt = $this->anonymizer->anonymizeMedicalText($userPrompt);

        try {
            $response = $this->circuitBreaker->call(function () use ($systemPrompt, $anonymizedUserPrompt) {
                return $this->openai->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $anonymizedUserPrompt],
                ], 0.3, 'json');
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                throw new \RuntimeException('AI service temporarily unavailable. Please try again later.');
            }
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to get AI diagnosis. Please try again later.');
        }

        $diagnosisData = json_decode($response['content'], true);
        $result = AIDiagnosticResultDto::fromArray($diagnosisData);
        $result->correlationId = $correlationId;

        $this->cache->put($cacheKey, json_encode($result->toArray()), self::CACHE_TTL);

        $this->logger->info('ai_diagnostic.completed', [
            'user_id' => $dto->userId,
            'correlation_id' => $correlationId,
        ]);

        return $result;
    }

    private function getPatientHistory(int $userId): array
    {
        return $this->db->table('medical_records')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function buildDiagnosticSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a medical AI assistant for a healthcare platform. Analyze symptoms and provide:
1. Possible diagnoses with confidence scores
2. Recommended tests
3. Urgency level (low, medium, high, emergency)
4. Health score (0-100)
5. Key risk factors

Return JSON with structure:
{
  "diagnoses": [{"name": "", "confidence": 0.8}],
  "recommended_tests": [],
  "urgency_level": "medium",
  "health_score": 75,
  "risk_factors": []
}
PROMPT;
    }

    private function buildDiagnosticUserPrompt(string $symptoms, array $history, ?string $context): string
    {
        $prompt = "Patient symptoms: {$symptoms}. ";
        
        if (!empty($history)) {
            $prompt .= "Medical history: " . json_encode($history) . ". ";
        }
        
        if ($context) {
            $prompt .= "Additional context: {$context}. ";
        }
        
        return $prompt;
    }
}
