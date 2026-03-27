<?php

declare(strict_types=1);

namespace App\Domains\Medical\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * КАНОН 2026 — AI MEDICAL TRIAGE SERVICE
 * Использование LLM для предварительной сортировки пациентов
 */
final readonly class AIMedicalTriageService
{
    public function __construct(
        private \App\Services\AI\AIConstructorService $aiConstructor,
        private \App\Domains\Medical\Models\Doctor $doctorModel
    ) {}

    /**
     * Анализ симптомов через AI Vision/Text
     */
    public function analyzeSymptoms(string $text, int $userId): array
    {
        $correlationId = (string)Str::uuid();

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

            Log::channel('audit')->info('AI Triage performed', [
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
            Log::channel('audit')->error('AI Triage failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
