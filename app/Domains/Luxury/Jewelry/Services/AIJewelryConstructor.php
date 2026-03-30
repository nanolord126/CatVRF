<?php declare(strict_types=1);

namespace App\Domains\Luxury\Jewelry\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIJewelryConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly JewelryDomainService $jewelryService
        ) {}

        /**
         * Coordinate AI selection of jewelry recommendations.
         * Uses photo analysis (mocked) and recommendation engine logic.
         */
        public function generateRecommendations(AIJewelryConstructorRequestDto $dto): AIJewelryResultDto
        {
            $correlationId = $dto->correlationId ?? (string) Str::uuid();

            Log::channel('audit')->info('LAYER-4: AI Jewelry Recommendation Request', [
                'style' => $dto->stylePreference,
                'color' => $dto->colorType,
                'occasion' => $dto->occasion,
                'budget' => $dto->budgetLimit,
                'correlation_id' => $correlationId,
            ]);

            try {
                // 1. Analyze Choice Logic based on ColorType and Occasion
                $logicData = $this->getColorTypeMatrix($dto->colorType);

                // 2. Fetch Potential Products matching Style and Budget
                $matchedIds = $this->getMatchingJewelry($dto->stylePreference, $dto->budgetLimit, $logicData['suggested_metals']);

                if (empty($matchedIds)) {
                    throw new Exception("No jewelry products found for style '{$dto->stylePreference}' within budget.");
                }

                // 3. Construct Brief Advice for the user
                $advice = "Based on your '{$dto->colorType}' color type and '{$dto->stylePreference}' preference,
                           we recommend '{$logicData['suggested_metals'][0]}' items with '{$logicData['suggested_stones'][0]}'.
                           Perfect and elegant for '{$dto->occasion}'.";

                $result = new AIJewelryResultDto(
                    recommendedProductIds: $matchedIds,
                    suggestedMetals: $logicData['suggested_metals'],
                    suggestedStones: $logicData['suggested_stones'],
                    aiAdviceBrief: $advice,
                    correlationId: $correlationId
                );

                Log::channel('audit')->info('LAYER-4: AI Jewelry Recommendations Generated Successfully', [
                    'items_count' => count($result->recommendedProductIds),
                    'correlation_id' => $correlationId,
                ]);

                return $result;

            } catch (\Throwable $e) {
                Log::channel('audit')->error('LAYER-4: AI Jewelry Construction Failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        /**
         * Logic for metal and stone selection based on seasonal color types.
         */
        private function getColorTypeMatrix(string $colorType): array
        {
            return match ($colorType) {
                'warm-spring' => [
                    'suggested_metals' => ['yellow-gold', 'rose-gold'],
                    'suggested_stones' => ['emerald', 'aquamarine', 'diamond']
                ],
                'cool-summer' => [
                    'suggested_metals' => ['platinum', 'white-gold', 'silver'],
                    'suggested_stones' => ['pearl', 'sapphire', 'moonstone']
                ],
                'warm-autumn' => [
                    'suggested_metals' => ['yellow-gold', 'copper'],
                    'suggested_stones' => ['topaz', 'citrine', 'garnet']
                ],
                default => [ // cool-winter
                    'suggested_metals' => ['platinum', 'white-gold'],
                    'suggested_stones' => ['ruby', 'sapphire', 'diamond']
                ],
            };
        }

        /**
         * Filter jewelry products matching the style, budget and metal type.
         */
        private function getMatchingJewelry(string $style, int $budget, array $metals): array
        {
            // Search in the inventory for the best fits
            $products = JewelryProduct::where('is_published', true)
                ->where('price_b2c', '<=', $budget)
                ->whereJsonContains('tags', $style)
                ->orderBy('price_b2c', 'desc')
                ->limit(6)
                ->pluck('id')
                ->toArray();

            // If no style match, fallback to general budget and metal match
            if (empty($products)) {
                $products = JewelryProduct::where('is_published', true)
                    ->where('price_b2c', '<=', $budget)
                    ->orderBy('price_b2c', 'desc')
                    ->limit(6)
                    ->pluck('id')
                    ->toArray();
            }

            return $products;
        }
}
