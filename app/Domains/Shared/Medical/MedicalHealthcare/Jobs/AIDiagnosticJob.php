<?php declare(strict_types=1);

namespace App\Domains\Shared\Medical\MedicalHealthcare\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Resilience\CircuitBreaker;
use App\Services\AI\OpenAIClientService;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Domains\Shared\Medical\MedicalHealthcare\Services\AI\MedicalDataAnonymizerService;

final class AIDiagnosticJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = [5, 10, 30];

    public function __construct(
        private readonly int $userId,
        private readonly string $cacheKey,
        private readonly string $systemPrompt,
        private readonly string $userPrompt,
        private readonly string $correlationId,
    ) {}

    public function handle(
        OpenAIClientService $openai,
        CircuitBreaker $circuitBreaker,
        MedicalDataAnonymizerService $anonymizer,
        AuditService $audit,
    ): array {
        // Анонимизация данных перед отправкой в OpenAI
        $anonymizedUserPrompt = $anonymizer->anonymizeMedicalText($this->userPrompt);

        try {
            $response = $circuitBreaker->call(function () use ($openai, $this->systemPrompt, $anonymizedUserPrompt) {
                return $openai->chat([
                    ['role' => 'system', 'content' => $this->systemPrompt],
                    ['role' => 'user', 'content' => $anonymizedUserPrompt],
                ], 0.3, 'json');
            });
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'Circuit breaker is open')) {
                Log::error('AI service circuit breaker open', [
                    'user_id' => $this->userId,
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
            throw $e;
        } catch (\Throwable $e) {
            Log::error('OpenAI API call failed in job', [
                'error' => $e->getMessage(),
                'user_id' => $this->userId,
                'correlation_id' => $this->correlationId,
            ]);
            throw new \RuntimeException('Failed to get AI diagnosis. Please try again later.');
        }

        // Audit log for AI call
        $audit->logAction(
            action: 'ai_diagnosis_llm_call',
            entityType: 'medical_diagnosis',
            entityId: $this->userId,
            context: [
                'user_id' => $this->userId,
                'correlation_id' => $this->correlationId,
                'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            ],
        );

        return $response;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('AIDiagnosticJob failed', [
            'error' => $exception->getMessage(),
            'user_id' => $this->userId,
            'correlation_id' => $this->correlationId,
        ]);
    }
}
