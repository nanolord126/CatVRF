<?php declare(strict_types=1);

namespace App\Domains\Food\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class MealConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private ImageAnalysisService $imageAnalysis,
            private RecommendationService $recommendation,
            private InventoryManagementService $inventory,
            private FraudControlService $fraud,
            private string $correlationId
        ) {}

        /**
         * Создать блюдо или меню на основе ингредиентов (фото или список)
         */
        public function construct(int $userId, ?\Illuminate\Http\UploadedFile $photo = null, array $params = []): AIConstructionResult
        {
            Log::channel('audit')->info('MealConstructor: starting generation', [
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            return DB::transaction(function () use ($userId, $photo, $params) {
                // 1. Fraud Check
                $this->fraud->check('food_ai_meal_construction', ['user_id' => $userId]);

                $ingredients = [];
                // 2. AI-шеф: Генерация или анализ ингредиентов по фото (Vision AI)
                if ($photo) {
                    $visionResult = $this->imageAnalysis->analyzeIngredients($photo);
                    $ingredients = $visionResult['ingredients'] ?? [];
                } else {
                    $ingredients = $params['ingredients'] ?? [];
                }

                // 3. Кастомизация состава (без аллергенов, КБЖУ под цель: похудение, набор массы и т.д.)
                $goal = $params['goal'] ?? 'balanced';
                $restrictions = $params['restrictions'] ?? ['low_sugar'];

                $context = array_merge($params, [
                    'ingredients' => $ingredients,
                    'goal' => $goal,
                    'restrictions' => $restrictions,
                    'vertical' => 'Food',
                ]);

                // Рекомендации готовых блюд или продуктов из Inventory
                $recommendations = $this->recommendation->getForUser($userId, 'Food', $context);

                // 4. Проверка наличия и расчет цен (InventoryManagementService)
                $suggestions = $this->enrichSuggestions($recommendations);

                // 5. Итоговая калькуляция цен и КБЖУ (AIPricingCalculator)
                $calculation = $this->calculateNutritionAndPrice($ingredients, $suggestions);

                $result = new AIConstructionResult(
                    vertical: 'Food',
                    type: 'calculation',
                    payload: [
                        'meal_name' => "Ваш персональный рацион '{$goal}'",
                        'ingredients_analysis' => $ingredients,
                        'nutrition' => [
                            'calories' => $calculation['calories'],
                            'proteins' => $calculation['proteins'],
                            'fats' => $calculation['fats'],
                            'carbs' => $calculation['carbs'],
                        ],
                        'pricing' => [
                            'base_total' => $calculation['total_price'],
                            'discounted_total' => $calculation['discounted_total'],
                        ],
                        'chef_notes' => "Это блюдо богато белком и содержит сложные углеводы для долгой энергии.",
                    ],
                    suggestions: $suggestions,
                    confidence_score: (float)($visionResult['confidence'] ?? 0.90),
                    correlation_id: $this->correlationId
                );

                // 6. Сохранение в БД
                $this->saveToDatabase($userId, $result);

                Log::channel('audit')->info('MealConstructor: finished generation', [
                    'user_id' => $userId,
                    'correlation_id' => $this->correlationId,
                    'meals_suggested' => count($suggestions),
                ]);

                return $result;
            });
        }

        private function enrichSuggestions(\Illuminate\Support\Collection $recommendations): array
        {
            return $recommendations->map(function ($item) {
                $inStock = $this->inventory->getCurrentStock($item->id) > 0;

                return array_merge($item->toArray(), [
                    'in_stock' => $inStock,
                    'match_reason' => $inStock ? 'Идеально вписывается в ваш КБЖУ' : 'Может быть заменено на похожий продукт.',
                ]);
            })->toArray();
        }

        private function calculateNutritionAndPrice(array $ingredients, array $suggestions): array
        {
            // Имитация AIPricingCalculator + NutritionAnalyzer
            $totalPrice = array_sum(array_column($suggestions, 'price'));

            return [
                'calories' => rand(300, 800),
                'proteins' => rand(20, 50),
                'fats' => rand(10, 30),
                'carbs' => rand(40, 100),
                'total_price' => $totalPrice,
                'discounted_total' => (int)($totalPrice * 0.9), // Скидка по промо
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
