<?php

namespace App\Services\B2B;

use App\Models\B2BProduct;
use App\Models\B2BOrder;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\HasEcosystemTracing;

/**
 * Service for Predictive Demand Forecasting and Dynamic Pricing in B2B Marketplace (2026 Canon).
 */
class B2BAIAnalyticsService
{
    use HasEcosystemTracing;

    /**
     * Forecasts demand for a product category in a specific tenant's context.
     * Uses historic orders, seasonality, and simulated marketplace events.
     */
    public function forecastDemand(string $category, Tenant $tenant): array
    {
        $now = Carbon::now();
        $thirtyDaysLater = $now->copy()->addDays(30);

        // 1. Historic Order Patterns (Last 12 months)
        $historicVolume = DB::table('b2b_products')
            ->join('b2b_bulk_order_items', 'b2b_products.id', '=', 'b2b_bulk_order_items.product_id')
            ->join('b2b_bulk_orders', 'b2b_bulk_order_items.bulk_order_id', '=', 'b2b_bulk_orders.id')
            ->where('b2b_products.specifications->category', $category) // Assuming category in JSON specs for flexibility
            ->where('b2b_bulk_orders.created_at', '>=', $now->copy()->subYear())
            ->sum('b2b_bulk_order_items.quantity');

        $averageMonthlyVolume = $historicVolume / 12;

        // 2. Seasonality Factor (Simulated holidays/events)
        $seasonalityFactor = $this->calculateSeasonality($now);

        // 3. Current Marketplace Events (Simulated local conferences/trends)
        $marketEventImpact = $this->getMarketplaceTrendImpact($category);

        // Prediction Formula
        $predictedVolume = ($averageMonthlyVolume * $seasonalityFactor) + $marketEventImpact;

        return [
            'category' => $category,
            'predicted_30d_volume' => round($predictedVolume, 2),
            'confidence_score' => 0.85, // 2026 AI confidence
            'factors' => [
                'seasonality' => $seasonalityFactor,
                'market_trends' => $marketEventImpact > 0 ? 'Positive' : 'Neutral',
            ],
            'prediction_date' => $now->toDateString(),
            'valid_until' => $thirtyDaysLater->toDateString(),
        ];
    }

    /**
     * Suggests the optimal price for a supplier based on elasticity, demand, and competitors.
     */
    public function suggestOptimalPrice(B2BProduct $product): array
    {
        $basePrice = (float) $product->base_wholesale_price;
        $currentDemand = $this->getMarketplaceTrendImpact($product->name); // Simplified name-based trend
        
        // Sibling product pricing (Competitors)
        $avgCompetitorPrice = DB::table('b2b_products')
            ->where('name', 'LIKE', "%{$product->name}%")
            ->where('id', '!=', $product->id)
            ->avg('base_wholesale_price') ?? $basePrice;

        // Price Elasticity Strategy: Balance profit vs turnover
        $demandMultiplier = $currentDemand > 100 ? 1.05 : 0.95; 
        $competitorFactor = $avgCompetitorPrice < $basePrice ? 0.98 : 1.02;

        $suggestedPrice = $basePrice * $demandMultiplier * $competitorFactor;

        // Potential Savings for the platform/buyers if holding inventory
        $potentialSavings = max(0, $basePrice - $suggestedPrice);

        return [
            'product_id' => $product->id,
            'current_price' => $basePrice,
            'suggested_price' => round($suggestedPrice, 2),
            'price_change_pc' => round((($suggestedPrice - $basePrice) / $basePrice) * 100, 2),
            'potential_savings_per_unit' => round($potentialSavings, 2),
            'reasoning' => $this->generatePricingReasoning($demandMultiplier, $competitorFactor),
        ];
    }

    private function calculateSeasonality(Carbon $date): float
    {
        $month = $date->month;
        // High demand in Q4 (Holidays) and Spring (Procurement cycles)
        if (in_array($month, [11, 12, 3, 4])) return 1.25;
        if (in_array($month, [7, 8])) return 0.8; // Summer slump
        return 1.0;
    }

    private function getMarketplaceTrendImpact(string $context): float
    {
        // Получить текущие тренды из базы данных для этого категория/контекста
        $trend = DB::table('ai_market_trends')
            ->whereRaw("MATCH(keyword) AGAINST(? IN NATURAL LANGUAGE MODE)", [$context])
            ->latest('updated_at')
            ->first();

        if ($trend) {
            // Вернуть реальное значение тренда из БД
            return (float)$trend->impact_multiplier * 100.0;
        }

        // Fallback: поискать базовый тренд по типу контекста
        $baseImpact = DB::table('b2b_category_trends')
            ->where('category_name', 'like', "%{$context}%")
            ->latest('trend_score')
            ->value('trend_score') ?? 50.0;

        return $baseImpact;
    }

    private function generatePricingReasoning(float $demand, float $comp): string
    {
        $reasons = [];
        if ($demand > 1) $reasons[] = "High seasonal demand detected.";
        if ($comp < 1) $reasons[] = "Competitor price pressure.";
        if (empty($reasons)) $reasons[] = "Market stability within normal bounds.";
        
        return implode(' ', $reasons);
    }
}
