<?php declare(strict_types=1);

namespace App\Domains\Education\LanguageLearning\Services;

use Carbon\Carbon;


use Psr\Log\LoggerInterface;
final readonly class AILearningPathConstructor
{

    public function __construct(
            private LanguagePricingService $pricing, private readonly LoggerInterface $logger
        ) {}

        /**
         * Создание персонального образовательного трека.
         * @param array $params [language, level, goal, weekly_hours, budget_limit]
         * @return array [plan, steps, recommended_courses, total_price]
         */
        public function constructPath(array $params, int $tenantId, string $correlationId): array
        {
            $this->logger->info('AI Learning Path Construction started', [
                'language' => $params['language'],
                'weekly_hours' => $params['weekly_hours'],
                'correlation_id' => $correlationId,
            ]);

            // Эмуляция весового подбора 2026 (в реальности AI-запрос к OpenAI/GigaChat)
            $learningPlan = $this->generateSteps(
                language: $params['language'],
                level: $params['level'] ?? 'A0',
                goal: $params['goal'] ?? 'general',
                hours: (int)$params['weekly_hours']
            );

            $recommendedCourses = $this->findRelevantCourses(
                language: $params['language'],
                level: $params['level'] ?? 'A0',
                budget: (int)($params['budget_limit'] ?? 100000)
            );

            $totalPathPrice = $recommendedCourses->sum('price_total');

            $result = [
                'title' => "Path to {$params['language']} Proficiency: " . ($params['goal'] ?? 'Discovery'),
                'steps' => $learningPlan,
                'recommended_courses' => $recommendedCourses->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'price' => $c->price_total,
                    'teacher' => $c->teacher->full_name,
                ]),
                'estimated_duration_weeks' => (int)(($enrollment_goal_hours = 120) / max($params['weekly_hours'], 1)),
                'total_price' => $totalPathPrice,
                'correlation_id' => $correlationId,
                'generated_at' => Carbon::now()->toIso8601String(),
            ];

            $this->logger->info('AI Learning Path generated successfully', [
                'total_price' => $totalPathPrice,
                'correlation_id' => $correlationId,
            ]);

            return $result;
        }

        /**
         * Генерация шагов обучения на основе уровня (ML-эмуляция).
         */
        private function generateSteps(string $language, string $level, string $goal, int $hours): array
        {
            $steps = [];

            // Шаг 1: Фундамент
            $steps[] = [
                'id' => 1,
                'title' => "Mastering {$language} Basics",
                'focus' => 'Phonetics & Vocabulary',
                'duration' => 4,
                'hours_per_week' => $hours,
            ];

            // Шаг 2: Грамматика
            $steps[] = [
                'id' => 2,
                'title' => 'Structural Grammar & Patterns',
                'focus' => 'Cases, Tenses, Modals',
                'duration' => 8,
                'hours_per_week' => $hours,
            ];

            // Шаг 3: Практика (в зависимости от цели)
            $steps[] = [
                'id' => 3,
                'title' => ($goal === 'business' ? 'Professional' : 'Conversational') . ' Immersion',
                'focus' => "Active speaking in {$goal} scenarios",
                'duration' => 12,
                'hours_per_week' => $hours,
            ];

            return $steps;
        }

        /**
         * Поиск курсов в текущем тенанте под бюджет и уровень.
         */
        private function findRelevantCourses(string $language, string $level, int $budget): Collection
        {
            return LanguageCourse::with('teacher')
                ->where('language', $language)
                ->where('price_total', '<=', $budget)
                ->where('level_from', $level)
                ->orderBy('rating', 'desc')
                ->limit(3)
                ->get();
        }
}
