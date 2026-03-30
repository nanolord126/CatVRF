<?php declare(strict_types=1);

namespace App\Domains\Education\Kids\Services;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AIKidsProductConstructor extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /**
         * @param Client $openai
         */
        public function __construct(
            private Client $openai,
        ) {}

        /**
         * Build a personalized toy collection based on age and budget.
         * Uses OpenAI to understand educational context and matched items from catalog.
         */
        public function recommendGifts(
            int $ageMonths,
            int $budgetKopecks,
            string $preference = 'educational',
            string $correlationId = null
        ): Collection {
            $cacheKey = "kids_recommendations:age_{$ageMonths}:budget_{$budgetKopecks}:pref_{$preference}";

            return Cache::remember($cacheKey, 3600, function () use ($ageMonths, $budgetKopecks, $preference, $correlationId) {
                Log::channel('audit')->info('AI Product Recommendation started', [
                    'age' => $ageMonths,
                    'budget' => $budgetKopecks,
                    'correlation_id' => $correlationId
                ]);

                // 1. Fetch available products based on age and budget
                $products = KidsProduct::available()
                    ->where('price', '<=', $budgetKopecks)
                    ->whereRaw("CAST(age_range->>'min_months' AS INTEGER) <= ?", [$ageMonths])
                    ->whereRaw("CAST(age_range->>'max_months' AS INTEGER) >= ?", [$ageMonths])
                    ->with('toy')
                    ->get();

                if ($products->isEmpty()) {
                    return collect([]);
                }

                // 2. Use OpenAI to rank and explain if preference is provided
                $productDescriptions = $products->map(fn($p) => "ID: {$p->id}, Name: {$p->name}, Description: {$p->description}")->implode("; ");

                $response = $this->openai->chat()->create([
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => "You are a professional children's educator and gift consultant. Pick top 3 products for a child aged {$ageMonths} months with a budget of " . ($budgetKopecks / 100) . " RUB. Preference: {$preference}."],
                        ['role' => 'user', 'content' => "Available products: {$productDescriptions}. Return only JSON as array of IDs: [id1, id2, id3]."]
                    ],
                    'response_format' => ['type' => 'json_object']
                ]);

                $resultIds = json_decode($response->choices[0]->message->content, true)['ids'] ?? [];

                Log::channel('audit')->info('AI Product Recommendation finished', [
                    'ids' => $resultIds,
                    'correlation_id' => $correlationId
                ]);

                return $products->whereIn('id', $resultIds)->values();
            });
        }

        /**
         * AI-based safety score analyzer based on product description and materials.
         */
        public function analyzeProductSafety(int $productId): float
        {
            $product = KidsProduct::findOrFail($productId);
            $materialString = json_encode($product->material_details);

            $response = $this->openai->chat()->create([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a product safety expert. Analyze materials and description for toxicity or hazards for children.'],
                    ['role' => 'user', 'content' => "Product: {$product->name}. Description: {$product->description}. Materials: {$materialString}. Return JSON: {'safety_score': 0..1.0, 'reason': 'text'}"]
                ],
                'response_format' => ['type' => 'json_object']
            ]);

            $analysis = json_decode($response->choices[0]->message->content, true);
            return (float) ($analysis['safety_score'] ?? 0.5);
        }
}
