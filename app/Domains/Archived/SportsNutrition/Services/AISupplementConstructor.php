<?php declare(strict_types=1);

namespace App\Domains\Archived\SportsNutrition\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AISupplementConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    private string $correlationId;


        public function __construct(


            private readonly RecommendationService $baseRecommendation,


            private readonly InventoryManagementService $inventory,


            private readonly FraudControlService $fraudControl


        ) {


            $this->correlationId = request()->header('X-Correlation-ID', (string) Str::uuid());


        }


        /**


         * Recommend a supplement stack based on AI goals.


         */


        public function constructStack(AISupplementRequestDto $dto, string $cid = null): AISupplementResultDto


        {


            $cid = $cid ?? $this->correlationId;


            Log::channel('recommend')->info('AI Supplement Stack Construction Inbound', [


                'cid' => $cid,


                'user' => $dto->user_id,


                'goal' => $dto->goal,


                'weight' => $dto->weight_kg,


                'diet' => $dto->dietary_restriction


            ]);


            // 1. Mandatory Security: Check for abuse or spam generation


            $this->fraudControl->check($cid, 'ai_stack_generate');


            // 2. Logic: Define macro requirements based on goal & weight


            $proteinPerKg = 1.6; // Base default (Maintenance)


            $caloriesAdjust = 0; // Surplus or deficit


            if ($dto->goal === 'bulking') {


                $proteinPerKg = 2.2;


                $caloriesAdjust = 500;


            } elseif ($dto->goal === 'cutting') {


                $proteinPerKg = 2.4;


                $caloriesAdjust = -500;


            } elseif ($dto->goal === 'endurance') {


                $proteinPerKg = 1.4;


                $caloriesAdjust = 300;


            }


            $dailyProteinTarget = (int) ($dto->weight_kg * $proteinPerKg);


            // 3. Query: Find products matching the dietary restrictions & macro needs


            $query = SportsNutritionProduct::query()->where('is_published', true);


            // Dietary Filtering


            if ($dto->dietary_restriction === 'vegan') {


                $query->where('is_vegan', true);


            } elseif ($dto->dietary_restriction === 'no-dairy') {


                $query->whereJsonDoesntContain('allergens', 'milk');


            }


            // Price Filtering


            $query->where('price_b2c', '<=', $dto->budget_kopecks_max);


            // Matching strategy


            $suggestions = $query->with('category')


                ->orderBy('rating', 'desc')


                ->limit(5)


                ->get();


            // 4. Construct the Result Payload


            $confidence = 0.85; // Simulated AI confidence based on data availability


            $stackName = match ($dto->goal) {


                'bulking' => 'Hyper-Mass AI Pro Stack',


                'cutting' => 'Elite-Cut AI Shredder',


                'recovery' => 'Night-Repair Recovery Core',


                'endurance' => 'Stamina-Max Endurance Fuel',


                default => 'Essential Daily Multi-Pack',


            };


            $result = new AISupplementResultDto(


                vertical: 'sports-nutrition',


                recommended_stack_name: $stackName,


                payload: [


                    'target_protein_daily_g' => $dailyProteinTarget,


                    'target_calories_daily' => (int) (2000 + $caloriesAdjust), // Simplistic calorie baseline


                    'formula_version' => 'sn-ai-v2.1',


                    'p_kg' => $proteinPerKg,


                    'adjust' => $caloriesAdjust,


                    'diet_filter' => $dto->dietary_restriction,


                ],


                suggestions: $suggestions,


                confidence_score: $confidence,


                correlation_id: $cid


            );


            Log::channel('recommend')->info('AI Supplement Stack Generated', [


                'cid' => $cid,


                'stack' => $stackName,


                'matches' => $suggestions->count(),


                'score' => $confidence


            ]);


            return $result;


        }


        /**


         * Check if a specific combination of supplements is safe (simplified logic).


         */


        public function validateCombination(array $productSkus): array


        {


            // Example: Don't recommend two high-stim pre-workouts


            $products = SportsNutritionProduct::whereIn('sku', $productSkus)->get();


            $stimCount = 0;


            foreach ($products as $product) {


                if (isset($product->tags) && in_array('high-stim', $product->tags)) {


                    $stimCount++;


                }


            }


            if ($stimCount > 1) {


                return ['safe' => false, 'warning' => 'Combining multiple high-stimulant products is not recommended.'];


            }


            return ['safe' => true, 'warning' => null];


        }
}
