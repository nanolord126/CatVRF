<?php

declare(strict_types=1);

namespace App\Domains\VeganProducts\Services;

use App\Domains\VeganProducts\Models\VeganProduct;
use App\Domains\VeganProducts\Models\VeganRecipe;
use App\Services\RecommendationService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AIVeganConstructorService - Layers 4/9: AI-Driven Personalization.
 * Generates plant-based menus and boxes based on diet, allergies, and goals.
 * Requirement: Final class, strict types, AI logic integration, correlation_id, audit.
 */
final readonly class AIVeganConstructorService
{
    public function __construct(
        private readonly RecommendationService $baseRecommendation,
        private readonly VeganProductService $productService,
    ) {}

    /**
     * Generate a personalized Vegan Box recommendation using AI.
     * Use case: "I want a high-protein, gluten-free vegan meal plan for 7 days."
     */
    public function generatePersonalizedBox(
        array $dietaryGoals,
        array $allergies,
        int $budgetInKopecks,
        ?string $correlationId = null
    ): array {
        $correlationId = $correlationId ?: (string) Str::uuid();

        Log::channel('audit')->info('LAYER-4: AI Vegan Box Generation START', [
            'goals' => $dietaryGoals,
            'allergies' => $allergies,
            'budget' => $budgetInKopecks,
            'correlation_id' => $correlationId,
        ]);

        try {
            // 1. Filter safe products via Domain Service (3/9)
            // Filters out specified allergies and ensures plant-based status.
            $safeProducts = $this->productService->findSafeProducts($allergies, $correlationId);

            if ($safeProducts->isEmpty()) {
                throw new Exception("No safe vegan products found matching the specified allergy profile.");
            }

            // 2. Select products matching DIETARY GOALS (AI logic simulation)
            // In production, this would call GPT-4o or a local LLM with the product context.
            $recommendedProducts = $this->selectProductsByDietaryGoals(
                $safeProducts,
                $dietaryGoals,
                $budgetInKopecks
            );

            // 3. Build AI-driven menu (Recipes)
            $suggestedRecipes = $this->findRecipesForProducts($recommendedProducts, $correlationId);

            // 4. Calculate final nutritional summary
            $summary = $this->calculateNutritionalSummary($recommendedProducts);

            Log::channel('audit')->info('LAYER-4: AI Vegan Box Generation SUCCESS', [
                'items_count' => $recommendedProducts->count(),
                'recipes_count' => $suggestedRecipes->count(),
                'correlation_id' => $correlationId,
            ]);

            return [
                'products' => $recommendedProducts->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price_b2c,
                    'protein' => $p->nutrition_info['protein_per_100g'] ?? 0,
                ]),
                'recipes' => $suggestedRecipes->map(fn($r) => [
                    'title' => $r->title,
                    'prep_time' => $r->preparation_time_minutes,
                ]),
                'nutrition_summary' => $summary,
                'total_cost' => $recommendedProducts->sum('price_b2c'),
                'correlation_id' => $correlationId,
            ];

        } catch (\Throwable $e) {
            Log::channel('audit')->error('LAYER-4: AI Generation FAILED', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);
            throw $e;
        }
    }

    /**
     * Logic for matching products to goals like 'high-protein', 'low-carb', 'detox'.
     */
    private function selectProductsByDietaryGoals(Collection $products, array $goals, int $budget): Collection
    {
        $selected = collect();
        $currentCost = 0;

        // Sorting by protein if goal is high-protein
        if (in_array('high-protein', $goals)) {
            $products = $products->sortByDesc(fn($p) => (float) ($p->nutrition_info['protein_per_100g'] ?? 0));
        }

        foreach ($products as $product) {
            $price = (int) $product->price_b2c;
            
            if (($currentCost + $price) <= $budget && $selected->count() < 12) {
                $selected->push($product);
                $currentCost += $price;
            }
        }

        return $selected;
    }

    /**
     * Find recipes that use at least one of the recommended products as a main ingredient.
     */
    private function findRecipesForProducts(Collection $products, string $correlationId): Collection
    {
        $productIds = $products->pluck('id')->toArray();
        
        return VeganRecipe::where(function($query) use ($productIds) {
            foreach ($productIds as $id) {
                $query->orWhereJsonContains('ingredient_ids', (int) $id);
            }
        })
        ->where('is_published', true)
        ->limit(3)
        ->get();
    }

    /**
     * Aggregate nutrition data across the whole recommendation set.
     */
    private function calculateNutritionalSummary(Collection $products): array
    {
        $sum = [
            'total_protein' => 0,
            'total_calories' => 0,
            'total_fat' => 0,
            'is_organic_heavy' => false,
        ];

        foreach ($products as $p) {
            $sum['total_protein'] += (float) ($p->nutrition_info['protein_per_100g'] ?? 0);
            $sum['total_calories'] += (int) ($p->nutrition_info['calories_per_100g'] ?? 0);
            $sum['total_fat'] += (float) ($p->nutrition_info['fat_per_100g'] ?? 0);
        }

        return $sum;
    }
}
