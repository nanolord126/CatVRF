<?php declare(strict_types=1);

namespace App\Domains\Travel\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AITripPlannerService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private RecommendationService $recommendation,
            private AIAgentFramework $agent,
        ) {}

        /**
         * Построить персональный план путешествия на базе AI.
         */
        public function generateTripPlan(int $userId, array $preferences, string $correlationId): array
        {
            Log::channel('audit')->info('AI trip planning started', [
                'user_id' => $userId,
                'preferences' => $preferences,
                'correlation_id' => $correlationId
            ]);

            try {
                // 1. Извлечь предпочтения (бюджет, тип: активный, семейный и т.д.)
                $prompt = $this->buildPrompt($preferences);

                // 2. Обращение к AI-агенту (Слой 5)
                $aiResponse = $this->agent->run('travel_planner', $prompt, [
                    'correlation_id' => $correlationId,
                    'user_id' => $userId
                ]);

                // 3. Сопоставить рекомендации AI с реальными данными из БД
                $matchedTours = $this->matchTours($aiResponse['tags'] ?? []);
                $matchedExcursions = $this->matchExcursions($aiResponse['tags'] ?? []);

                // 4. Персонализация (Слой 5 + RecommendationService)
                $personalizedTours = $this->recommendation->personalizeForUser($matchedTours, $userId);

                // 5. Кэширование и возврат результата
                $result = [
                    'plan_id' => (string) Str::uuid(),
                    'summary' => $aiResponse['summary'] ?? 'Индивидуальный план путешествия',
                    'destinations' => $aiResponse['destinations'] ?? [],
                    'recommended_tours' => $personalizedTours->take(5)->toArray(),
                    'recommended_excursions' => $matchedExcursions->take(3)->toArray(),
                    'daily_activities' => $aiResponse['itinerary'] ?? [],
                    'estimated_price' => $aiResponse['total_budget'] ?? 0,
                    'correlation_id' => $correlationId
                ];

                $this->savePlan($userId, $result);

                Log::channel('audit')->info('AI trip planning successfully completed', [
                    'user_id' => $userId,
                    'plan_id' => $result['plan_id'],
                    'correlation_id' => $correlationId
                ]);

                return $result;
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AI trip planning failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'correlation_id' => $correlationId
                ]);

                throw $e;
            }
        }

        private function buildPrompt(array $preferences): string
        {
            return sprintf(
                "Создай маршрут путешествия. Бюджет: %s. Тип: %s. Интересы: %s. Продолжительность: %s дней.",
                $preferences['budget'] ?? 'средний',
                $preferences['type'] ?? 'отдых',
                implode(', ', $preferences['interests'] ?? []),
                $preferences['days'] ?? 7
            );
        }

        private function matchTours(array $tags): Collection
        {
            return Tour::where('is_active', true)
                ->whereJsonContains('tags', $tags)
                ->with(['destination', 'trips' => fn($q) => $q->where('departure_date', '>', now())])
                ->get();
        }

        private function matchExcursions(array $tags): Collection
        {
            return Excursion::where('status', 'active')
                ->whereJsonContains('tags', $tags)
                ->get();
        }

        private function savePlan(int $userId, array $plan): void
        {
            // КАНОН: Сохранение в User AI Designs (Слой 5)
            \Illuminate\Support\Facades\DB::table('user_ai_designs')->insert([
                'user_id' => $userId,
                'vertical' => 'travel',
                'design_data' => json_encode($plan),
                'correlation_id' => $plan['correlation_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
}
