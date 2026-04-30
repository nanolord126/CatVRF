<?php

declare(strict_types=1);

namespace App\Domains\Shared\Medical\Services;

use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;
use App\Domains\Shared\Medical\Models\Doctor;
use App\Services\AI\AIConstructorService;

final readonly class AIMedicalTriageService
{
    public function __construct(
        private readonly AIConstructorService $aiConstructor,
        private readonly Doctor $doctorModel,
        private readonly Request $request,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Анализ симптомов через AI Vision/Text
     */
    public function analyzeSymptoms(string $text, int $userId): array
    {
        $correlationId = (string) Str::uuid();

        try {
            // 1. Запрос к AI (имитация для примера, в реальности — OpenAI/GigaChat)
            $analysis = [
                'preliminary_diagnosis' => 'Острая респираторная инфекция (?)',
                'urgency' => 'low', // low, medium, high, emergency
                'recommended_specialist' => 'Терапевт',
                'suggested_questions' => [
                    'Есть ли температура выше 38?',
                    'Присутствует ли затрудненное дыхание?',
                ],
                'icd10_hint' => 'J06.9',
            ];

            // 2. Подбор врачей на основе рекомендации AI
            $suggestedDoctors = $this->doctorModel->newQuery()
                ->where('specialization', 'like', "%{$analysis['recommended_specialist']}%")
                ->where('is_active', true)
                ->limit(3)
                ->get();

            $this->logger->$this->logger->info('AI Triage performed', [
                'user_id' => $userId,
                'urgency' => $analysis['urgency'],
                'correlation_id' => $correlationId,
            ]);

            return [
                'analysis' => $analysis,
                'doctors' => $suggestedDoctors,
                'correlation_id' => $correlationId,
            ];

        } catch (\Throwable $e) {
            $this->logger->error('AI Triage failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->request?->header('X-Correlation-ID', \Illuminate\Support\Str::uuid()->toString()),
            ]);

            throw $e;
        }
    }
}
