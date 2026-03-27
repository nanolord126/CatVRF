<?php

declare(strict_types=1);

namespace App\Domains\Education\Courses\Services;

use App\DTOs\AI\AIConstructionResult;
use App\Services\AI\ImageAnalysisService;
use App\Services\FraudControlService;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RoadmapConstructor (Канон 2026)
 * AI-куратор для генерации индивидуальных траекторий обучения (Roadmap) и тестов.
 */
final readonly class RoadmapConstructor
{
    public function __construct(
        private RecommendationService $recommendation,
        private FraudControlService $fraud,
        private string $correlationId
    ) {}

    /**
     * Создать личную образовательную траекторию
     */
    public function construct(int $userId, string $goal, array $params = []): AIConstructionResult
    {
        Log::channel('audit')->info('RoadmapConstructor: starting edu path', [
            'user_id' => $userId,
            'goal' => $goal,
            'correlation_id' => $this->correlationId,
        ]);

        return DB::transaction(function () use ($userId, $goal, $params) {
            // 1. Fraud Check (лимит генераций роадмапов)
            $this->fraud->check('education_ai_roadmap', ['user_id' => $userId]);

            // 2. Генерация траектории (Roadmap) на 30-90 дней
            $level = $params['current_level'] ?? 'beginner';
            $hoursPerWeek = $params['hours_per_week'] ?? 10;

            $context = array_merge($params, [
                'goal' => $goal,
                'current_level' => $level,
                'available_time' => $hoursPerWeek,
                'vertical' => 'Education',
            ]);

            // 3. Подбор курсов и уроков (EducationDomain)
            $recommendations = $this->recommendation->getForUser($userId, 'Education', $context);

            // 4. Формирование плана занятий (Output) на 30 дней
            $roadmapSteps = $this->generateRoadmapSteps($goal, $level, $recommendations);

            $result = new AIConstructionResult(
                vertical: 'Education',
                type: 'list',
                payload: [
                    'roadmap_title' => "Ваш путь к цели: '{$goal}'",
                    'steps' => $roadmapSteps,
                    'ai_coach_advice' => "Начинайте с основ и уделяйте не меньше {$hoursPerWeek} часов в неделю. Упор на практику — ключ к успеху.",
                    'duration_estimate_days' => 30,
                    'curator_score' => 0.94,
                ],
                suggestions: $recommendations->toArray(),
                confidence_score: 0.94,
                correlation_id: $this->correlationId
            );

            // 5. Сохранение в БД
            $this->saveToDatabase($userId, $result);

            Log::channel('audit')->info('RoadmapConstructor: roadmap finished', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            return $result;
        });
    }

    private function generateRoadmapSteps(string $goal, string $level, \Illuminate\Support\Collection $courses): array
    {
        // Имитация AI генерации шагов на базе найденных курсов
        return [
            ['day' => 1, 'topic' => 'Введение в ' . $goal, 'content_id' => $courses->first()->id ?? null, 'task' => 'Пройти вступительный тест'],
            ['day' => 7, 'topic' => 'Основные инструменты', 'content_id' => $courses->get(1)->id ?? null, 'task' => 'Практическое задание №1'],
            ['day' => 14, 'topic' => 'Глубокое погружение', 'content_id' => $courses->get(2)->id ?? null, 'task' => 'Разбор кейса №1'],
            ['day' => 30, 'topic' => 'Финальный проект', 'content_id' => null, 'task' => 'Сдача курсовой работы'],
        ];
    }

    private function saveToDatabase(int $userId, AIConstructionResult $result): void
    {
        DB::table('ai_constructions')->insert([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'user_id' => $userId,
            'tenant_id' => tenant()->id ?? 0,
            'vertical' => $result->vertical,
            'design_data' => json_encode($result->payload),
            'suggestions' => json_encode($result->suggestions),
            'correlation_id' => $result->correlation_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
