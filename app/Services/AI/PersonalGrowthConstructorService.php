<?php declare(strict_types=1);

namespace App\Services\AI;


use Illuminate\Http\Request;
use App\Services\RecommendationService;


use Illuminate\Support\Str;
use Throwable;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

final readonly class PersonalGrowthConstructorService
{
    public function __construct(
        private readonly Request $request,
        private RecommendationService $recommendationService,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

        /**
         * Создать персональный план роста (AI-генерация).
         *
         * @param \App\Models\User $user
         * @param array $input [goals, challenges, focus_areas, availability]
         * @return array [plan, suggestions, rationale]
         * @throws Throwable
         */
        public function generateGrowthPlan(\App\Models\User $user, array $input): array
        {
            $correlationId = $this->request->header('X-Correlation-ID') ?? Str::uuid()->toString();

            $this->logger->channel('audit')->info('AI Growth Constructor: Starting plan generation', [
                'user_id' => $user->id,
                'goals_count' => count($input['goals'] ?? []),
                'correlation_id' => $correlationId,
            ]);

            try {
                // 1. Получение психологического профиля пользователя (UserTasteProfile v2.0)
                $tasteProfile = $user->taste_profile ?? [];

                // 2. Симуляция запроса к LLM (OpenAI или GigaChat Pro)
                $analysisResult = $this->analyzeGoalsWithAI($user, $input, $tasteProfile);

                // 3. Подбор релевантных программ и коучей через RecommendationService
                $suggestions = $this->recommendationService->getForUser(
                    userId: $user->id,
                    vertical: 'pd',
                    context: [
                        'focus_areas' => $input['focus_areas'] ?? [],
                        'intensity' => $input['intensity'] ?? 'balanced',
                        'correlation_id' => $correlationId,
                    ]
                );

                // 4. Формирование финального плана
                $finalPlan = [
                    'success' => true,
                    'user_uuid' => $user->uuid,
                    'rationale' => $analysisResult['rationale'],
                    'roadmap' => $analysisResult['roadmap'],
                    'suggested_verticals' => $suggestions->map(fn($item) => [
                        'title' => $item['name'] ?? $item->title,
                        'type' => class_basename($item),
                        'match_score' => rand(85, 99) / 100,
                    ]),
                    'correlation_id' => $correlationId,
                ];

                // 5. Сохранение конструкции в БД
                $this->saveAIConstruction($user, $input, $finalPlan, $correlationId);

                $this->logger->channel('audit')->info('AI Growth Constructor: Plan successfully generated', [
                    'user_id' => $user->id,
                    'suggestions_count' => count($finalPlan['suggested_verticals']),
                    'correlation_id' => $correlationId,
                ]);

                return $finalPlan;

            } catch (Throwable $e) {
                $this->logger->channel('audit')->error('AI Growth Constructor Error', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Имитация работы LLM лог-анализатора целей.
         */
        private function analyzeGoalsWithAI(\App\Models\User $user, array $input, array $profile): array
        {
            // В продакшене здесь вызов OpenAI API или внутренней ML-модели
            return [
                'rationale' => "На основе ваших целей по развитию софт-скиллов и текущего интереса к тайм-менеджменту, мы рекомендуем сфокусироваться на дисциплине и системности.",
                'roadmap' => [
                    ['week' => 1, 'task' => 'Анализ текущего расписания', 'goal' => 'Найти 2 свободных часа в день'],
                    ['week' => 2, 'task' => 'Знакомство с техникой Pomodoro', 'goal' => 'Повысить концентрацию на 25%'],
                    ['week' => 3, 'task' => 'Первая сессия с коучем по тайм-менеджменту', 'goal' => 'Корректировка плана'],
                    ['week' => 4, 'task' => 'Постановка целей на месяц по SMART', 'goal' => 'Фокусировка на главном'],
                ],
            ];
        }

        /**
         * Сохранение сессии генерации (Канон: Все AI-конструкции сохраняются).
         */
        private function saveAIConstruction(\App\Models\User $user, array $input, array $plan, string $correlationId): void
        {
            $this->db->table('user_ai_designs')->insert([
                'user_id' => $user->id,
                'vertical' => 'pd',
                'design_data' => json_encode([
                    'input' => $input,
                    'result' => $plan,
                ]),
                'correlation_id' => $correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
}
