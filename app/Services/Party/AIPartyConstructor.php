<?php declare(strict_types=1);

namespace App\Services\Party;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIPartyConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public function __construct(
            private AIConstructorService $aiService,
            private string $correlationId
        ) {}

        /**
         * Build AI-matching party list.
         */
        public function buildDecorPlan(array $params): array
        {
            Log::channel('audit')->info('Initializing AI Party Constructor', [
                'correlation_id' => $this->correlationId,
                'budget' => $params['budget'] ?? 0,
                'guests' => $params['guests'] ?? 0,
                'theme_id' => $params['theme_id'] ?? null,
            ]);

            try {
                // Find theme
                $theme = isset($params['theme_id']) ? PartyTheme::find($params['theme_id']) : null;
                $themeName = $theme ? $theme->name : 'General Celebration';

                // Construct prompt for AI analysis (simplified here for simulation)
                $prompt = "Create matching balloon and decor list for a '{$themeName}' party.
                           Budget: {$params['budget']} cents. Guests: {$params['guests']}.";

                // (Simulation of AI analysis output)
                $analysis = [
                    'vertical' => 'party_supplies',
                    'recommendations' => [
                        'balloons' => 50,
                        'centerpieces' => 5,
                        'is_large' => $params['guests'] > 100,
                    ],
                ];

                // Filter real products from inventory based on AI analysis
                $matchedProducts = $this->matchProductsWithAnalysis($analysis, $theme);

                $result = [
                    'theme' => $themeName,
                    'analysis' => $analysis,
                    'matched_products' => $matchedProducts,
                    'is_b2b' => ($params['budget'] > 1000000), // Larger budgets imply B2B wholesale
                    'correlation_id' => $this->correlationId,
                ];

                Log::channel('audit')->info('AI Decor Plan successfully built', [
                    'correlation_id' => $this->correlationId,
                    'products_count' => count($matchedProducts),
                ]);

                return $result;

            } catch (Exception $e) {
                Log::channel('audit')->error('Failed to build AI Party Decor Plan', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);

                throw $e;
            }
        }

        /**
         * Filter actual store inventory matches based on AI suggestions.
         */
        private function matchProductsWithAnalysis(array $analysis, ?PartyTheme $theme): Collection
        {
            $query = PartyProduct::where('is_active', true);

            if ($theme) {
                $query->where('party_theme_id', $theme->id);
            }

            return $query->limit(10)->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price_cents' => $product->price_cents,
                    'sku' => $product->sku,
                    'stock' => $product->current_stock,
                ];
            });
        }

        /**
         * Match gift sets based on theme and guests count.
         */
        public function getGiftSetsByTheme(int $themeId): Collection
        {
            return PartyGiftSet::where('is_active', true)
                ->where('party_theme_id', $themeId)
                ->get();
        }
}
