<?php declare(strict_types=1);

namespace App\Domains\Furniture\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIInteriorConstructorService extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private readonly RecommendationService $generalRecommendations,
            private readonly FurnitureDomainService $furnitureService
        ) {}

        /**
         * Coordinate AI selection of products for an interior.
         * Uses photo analysis (mocked) and recommendation engine.
         */
        public function generateInteriorSetup(AIInteriorRequestDto $dto): AIInteriorResultDto
        {
            $correlationId = $dto->correlationId ?? (string) Str::uuid();

            Log::channel('audit')->info('LAYER-4: AI Interior Constructor Request', [
                'room_type' => $dto->roomTypeId,
                'style' => $dto->stylePreference,
                'budget' => $dto->budgetKopecks,
                'correlation_id' => $correlationId,
            ]);

            try {
                // 1. Analyze Room Photo (AI Vision Mock)
                $styleAnalysis = $this->analyzePhotoStyle($dto->photoPath, $dto->stylePreference);

                // 2. Fetch Available Products for Room Type + Style
                $products = $this->getMatchingProducts($dto->roomTypeId, $dto->stylePreference);

                if ($products->isEmpty()) {
                    throw new Exception("No products found for room type #{$dto->roomTypeId} with style '{$dto->stylePreference}'.");
                }

                // 3. Selection Algorithm (Budget-aware + Style-score)
                $selectedItems = $this->selectItemsWithinBudget($products, $dto->budgetKopecks, $dto->existingFurnitureIds);

                $result = new AIInteriorResultDto(
                    recommendedProductIds: $selectedItems->pluck('id')->toArray(),
                    estimatedTotal: $selectedItems->sum('price_b2c'),
                    layoutStrategy: "Center-focused distribution matching '{$dto->stylePreference}' style.",
                    styleAnalysis: $styleAnalysis,
                    correlationId: $correlationId
                );

                Log::channel('audit')->info('LAYER-4: AI Interior Layout Generated Successfully', [
                    'total_items' => count($result->recommendedProductIds),
                    'total_cost' => $result->estimatedTotal,
                    'correlation_id' => $correlationId,
                ]);

                return $result;

            } catch (\Throwable $e) {
                Log::channel('audit')->error('LAYER-4: AI Construction Failed', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                ]);
                throw $e;
            }
        }

        /**
         * AI Analysis of room photo for colors and textures.
         */
        private function analyzePhotoStyle(?string $photoPath, string $prefStyle): array
        {
            // Mocking OpenAI Vision analysis
            return [
                'primary_color' => '#f5f5f5',
                'dominant_texture' => 'wood',
                'detected_objects' => ['window', 'floor', 'empty_wall'],
                'matched_style' => $prefStyle,
                'confidence' => 0.92
            ];
        }

        /**
         * Filter furniture products mapping to the room and style.
         */
        private function getMatchingProducts(int $roomTypeId, string $style): \Illuminate\Database\Eloquent\Collection
        {
            return FurnitureProduct::whereJsonContains('recommended_room_types', $roomTypeId)
                ->whereJsonContains('tags', $style)
                ->where('stock_quantity', '>', 0)
                ->get();
        }

        /**
         * Greedy algorithm to pick items within budget.
         */
        private function selectItemsWithinBudget(\Illuminate\Database\Eloquent\Collection $products, int $budget, array $excludeIds): \Illuminate\Support\Collection
        {
            $selected = collect();
            $total = 0;

            // Skip excluded items (existing furniture) and sort by popularity (mocked via rating or ID)
            $candidates = $products->whereNotIn('id', $excludeIds)->sortByDesc('price_b2c');

            foreach ($candidates as $p) {
                if (($total + $p->price_b2c) <= $budget && $selected->count() < 10) {
                    $selected->push($p);
                    $total += $p->price_b2c;
                }
            }

            return $selected;
        }
}
