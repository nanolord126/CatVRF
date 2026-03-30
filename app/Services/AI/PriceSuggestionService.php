<?php declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Connection;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

/**
 * Dynamic Price Suggestion Service (CANON 2026)
 *
 * Provides intelligent price recommendations based on:
 * - Demand elasticity (conversion rates, velocity)
 * - Competitor pricing (market position)
 * - Inventory levels (stock pressure)
 * - Seasonality and trends
 * - Tenant-specific pricing rules
 * - User segment strategies
 *
 * All prices in kopeks (₽ × 100)
 */
final readonly class PriceSuggestionService
{
    private const CACHE_TTL_SHORT = 300;      // 5 min for volatile data
    private const CACHE_TTL_MEDIUM = 1800;    // 30 min for demand data
    private const CACHE_TTL_LONG = 3600;      // 1 hour for seasonal data

    private const MIN_PRICE_THRESHOLD = 0.70;  // Never below 70% of cost
    private const MAX_PRICE_THRESHOLD = 3.0;   // Never above 300% of cost
    private const PRICE_CHANGE_MAX = 0.30;     // Max 30% change per suggestion

    public function __construct(
        private readonly Connection $db,
        private readonly LogManager $log,
        private readonly Repository $cache,
    ) {}

    /**
     * Get price suggestion for a product/service
     * Combines demand, competition, inventory, and seasonality
     *
     * @param int $itemId Product/service ID
     * @param int $currentPrice Current price in kopeks
     * @param int $costPrice Cost price in kopeks
     * @param int $tenantId Tenant ID (for tenant-specific rules)
     * @param array $context Additional context (inventory, min_margin, etc.)
     * @param string|null $correlationId For distributed tracing
     * @return array ['suggested_price', 'lower_bound', 'upper_bound', 'confidence', 'factors', 'correlation_id']
     */
    public function suggestPrice(
        int $itemId,
        int $currentPrice,
        int $costPrice,
        int $tenantId,
        array $context = [],
        ?string $correlationId = null,
    ): array {
        $correlationId ??= Str::uuid()->toString();

        try {
            $cacheKey = "price_suggestion:tenant:{$tenantId}:item:{$itemId}";

            // Check cache first
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return array_merge($cached, ['correlation_id' => $correlationId, 'from_cache' => true]);
            }

            // 1. Calculate demand factor (conversion rate, velocity)
            $demandFactor = $this->calculateDemandFactor($itemId, $context);

            // 2. Calculate competition factor (competitor pricing)
            $competitionFactor = $this->calculateCompetitionFactor($itemId, $currentPrice, $context);

            // 3. Calculate inventory factor (stock pressure)
            $inventoryFactor = $this->calculateInventoryFactor($itemId, $context);

            // 4. Calculate seasonality factor (time-based trends)
            $seasonalityFactor = $this->calculateSeasonalityFactor($itemId, $context);

            // 5. Calculate tenant-specific rules
            $tenantRuleFactor = $this->calculateTenantRuleFactor($tenantId, $context);

            // 6. Blend all factors (weighted average)
            $blendedFactor = $this->blendFactors(
                demand: $demandFactor,      // 35%
                competition: $competitionFactor,  // 30%
                inventory: $inventoryFactor,      // 20%
                seasonality: $seasonalityFactor,  // 10%
                tenantRule: $tenantRuleFactor,    // 5%
            );

            // 7. Calculate suggested price
            $suggestedPrice = (int)($currentPrice * $blendedFactor);

            // 8. Apply bounds
            $lowerBound = max((int)($costPrice * self::MIN_PRICE_THRESHOLD), (int)($currentPrice * 0.75));
            $upperBound = min((int)($costPrice * self::MAX_PRICE_THRESHOLD), (int)($currentPrice * 1.30));

            // 9. Apply maximum change limit
            $maxChange = (int)($currentPrice * self::PRICE_CHANGE_MAX);
            $finalPrice = max(
                $currentPrice - $maxChange,
                min($upperBound, max($lowerBound, $suggestedPrice)),
            );

            // 10. Calculate confidence score
            $confidence = $this->calculateConfidence($demandFactor, $competitionFactor, $inventoryFactor);

            // 11. Build result
            $result = [
                'current_price' => $currentPrice,
                'suggested_price' => $finalPrice,
                'lower_bound' => $lowerBound,
                'upper_bound' => $upperBound,
                'cost_price' => $costPrice,
                'confidence' => $confidence,
                'reason' => $this->generateReason($finalPrice, $currentPrice, $demandFactor),
                'factors' => [
                    'demand' => round($demandFactor, 3),
                    'competition' => round($competitionFactor, 3),
                    'inventory' => round($inventoryFactor, 3),
                    'seasonality' => round($seasonalityFactor, 3),
                    'tenant_rule' => round($tenantRuleFactor, 3),
                    'blended' => round($blendedFactor, 3),
                ],
                'margin_percent' => round((($finalPrice - $costPrice) / $costPrice) * 100, 1),
            ];

            // 12. Cache result (with appropriate TTL)
            $cacheTtl = $this->determineCacheTTL($demandFactor, $inventoryFactor);
            Cache::put($cacheKey, $result, $cacheTtl);

            // 13. Audit log
            DB::transaction(function () use (
                $itemId,
                $tenantId,
                $currentPrice,
                $finalPrice,
                $demandFactor,
                $competitionFactor,
                $confidence,
                $correlationId,
            ) {
                Log::channel('audit')->info('PriceML: suggestion generated', [
                    'correlation_id' => $correlationId,
                    'tenant_id' => $tenantId,
                    'item_id' => $itemId,
                    'current_price' => $currentPrice,
                    'suggested_price' => $finalPrice,
                    'change_percent' => round((($finalPrice - $currentPrice) / $currentPrice) * 100, 1),
                    'demand_factor' => $demandFactor,
                    'competition_factor' => $competitionFactor,
                    'confidence' => $confidence,
                ]);
            });

            return array_merge($result, ['correlation_id' => $correlationId, 'from_cache' => false]);
        } catch (\Throwable $e) {
            Log::channel('fraud_alert')->error('PriceML: suggestion failed', [
                'correlation_id' => $correlationId,
                'item_id' => $itemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback: return current price with low confidence
            return [
                'current_price' => $currentPrice,
                'suggested_price' => $currentPrice,
                'lower_bound' => (int)($currentPrice * 0.90),
                'upper_bound' => (int)($currentPrice * 1.10),
                'confidence' => 0.1,
                'reason' => 'Pricing engine unavailable - keeping current price',
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'from_cache' => false,
            ];
        }
    }

    /**
     * Calculate demand factor based on conversion rate and velocity
     * Returns 0.5-1.5 (1.0 = average demand)
     */
    private function calculateDemandFactor(int $itemId, array $context): float
    {
        $last30Days = now()->subDays(30)->startOfDay();

        // Views in last 30 days
        $views = $context['views_30d'] ?? DB::table('product_views')
            ->where('product_id', $itemId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Sales in last 30 days
        $sales = $context['sales_30d'] ?? DB::table('order_items')
            ->where('product_id', $itemId)
            ->where('created_at', '>=', $last30Days)
            ->count();

        // Conversion rate
        $conversionRate = $views > 0 ? ($sales / $views) : 0;

        // Trend (sales last 7 days vs previous 7 days)
        $salesLast7 = DB::table('order_items')
            ->where('product_id', $itemId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $salesPrev7 = DB::table('order_items')
            ->where('product_id', $itemId)
            ->where('created_at', '>=', now()->subDays(14))
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        $trend = $salesPrev7 > 0 ? ($salesLast7 / $salesPrev7) : 1.0;

        // Determine demand factor
        if ($conversionRate > 0.15) {
            return min(1.5, 1.0 + ($conversionRate * 0.5));  // Very high demand
        } elseif ($conversionRate > 0.08) {
            return 1.3;  // High demand
        } elseif ($conversionRate > 0.03) {
            return 1.1;  // Good demand
        } elseif ($conversionRate < 0.01 && $views > 100) {
            return 0.70;  // Low conversion despite interest
        } elseif ($views < 5) {
            return 0.60;  // Very low interest
        }

        // Adjust for trend
        return max(0.5, min(1.5, 1.0 * $trend));
    }

    /**
     * Calculate competition factor based on competitor pricing
     * Returns 0.7-1.3 (1.0 = average market position)
     */
    private function calculateCompetitionFactor(int $itemId, int $currentPrice, array $context): float
    {
        $product = DB::table('products')->find($itemId);
        if (!$product) {
            return 1.0;
        }

        // Get competitor average price in same category
        $competitorAvgPrice = $context['competitor_avg_price'] ?? DB::table('products')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $itemId)
            ->where('status', 'active')
            ->avg('price');

        if (!$competitorAvgPrice) {
            return 1.0;
        }

        $priceDifference = ($currentPrice - $competitorAvgPrice) / $competitorAvgPrice;

        if ($priceDifference < -0.25) {
            return 1.20;  // We're 25%+ cheaper, can increase
        } elseif ($priceDifference < -0.10) {
            return 1.10;  // We're 10-25% cheaper
        } elseif ($priceDifference > 0.35) {
            return 0.80;  // We're 35%+ more expensive, need reduction
        } elseif ($priceDifference > 0.15) {
            return 0.90;  // We're 15-35% more expensive
        }

        return 1.0;
    }

    /**
     * Calculate inventory factor based on stock levels
     * Returns 0.8-1.2 (1.0 = optimal stock)
     */
    private function calculateInventoryFactor(int $itemId, array $context): float
    {
        $currentStock = $context['current_stock'] ?? DB::table('inventory_items')
            ->where('product_id', $itemId)
            ->value('current_stock') ?? 0;

        $minThreshold = $context['min_threshold'] ?? 10;
        $maxThreshold = $context['max_threshold'] ?? 100;

        if ($currentStock < ($minThreshold / 2)) {
            return 1.20;  // Critical low stock - can increase price
        } elseif ($currentStock < $minThreshold) {
            return 1.10;  // Low stock
        } elseif ($currentStock > ($maxThreshold * 1.5)) {
            return 0.85;  // Overstocked - need to reduce price
        } elseif ($currentStock > $maxThreshold) {
            return 0.90;  // High stock
        }

        return 1.0;  // Optimal range
    }

    /**
     * Calculate seasonality factor based on time and trends
     * Returns 0.8-1.3
     */
    private function calculateSeasonalityFactor(int $itemId, array $context): float
    {
        $month = (int)now()->format('m');
        $quarter = (int)ceil($month / 3);

        // Historical sales for this month vs average
        $historyMonthSales = DB::table('order_items')
            ->where('product_id', $itemId)
            ->whereRaw('MONTH(created_at) = ?', [$month])
            ->where('created_at', '>=', now()->subYears(2))
            ->count();

        $totalSales = DB::table('order_items')
            ->where('product_id', $itemId)
            ->where('created_at', '>=', now()->subYears(2))
            ->count();

        if ($totalSales === 0) {
            return 1.0;
        }

        $seasonalRatio = $historyMonthSales / ($totalSales / 12);

        // Convert to price factor: high ratio = high price
        return max(0.8, min(1.3, $seasonalRatio * 0.5 + 0.75));
    }

    /**
     * Calculate tenant-specific rule factor
     * Allows tenants to override pricing strategy
     */
    private function calculateTenantRuleFactor(int $tenantId, array $context): float
    {
        $rule = $context['pricing_rule'] ?? 'standard';

        return match ($rule) {
            'aggressive_growth' => 1.15,  // Increase by 15%
            'competitive' => 0.95,        // Decrease by 5%
            'value_leader' => 0.85,       // Decrease by 15%
            'premium' => 1.10,            // Increase by 10%
            default => 1.0,               // Standard
        };
    }

    /**
     * Blend all factors with their weights
     */
    private function blendFactors(
        float $demand,
        float $competition,
        float $inventory,
        float $seasonality,
        float $tenantRule,
    ): float {
        return ($demand * 0.35) +
               ($competition * 0.30) +
               ($inventory * 0.20) +
               ($seasonality * 0.10) +
               ($tenantRule * 0.05);
    }

    /**
     * Calculate confidence score (0-1)
     * Higher confidence when factors are stable
     */
    private function calculateConfidence(
        float $demand,
        float $competition,
        float $inventory,
    ): float {
        // Confidence decreases when factors are extreme
        $demandConfidence = 1.0 - (abs($demand - 1.0) * 0.3);
        $competitionConfidence = 1.0 - (abs($competition - 1.0) * 0.25);
        $inventoryConfidence = 1.0 - (abs($inventory - 1.0) * 0.2);

        $blended = ($demandConfidence * 0.5) +
                   ($competitionConfidence * 0.35) +
                   ($inventoryConfidence * 0.15);

        return round(max(0.2, min(0.99, $blended)), 2);
    }

    /**
     * Generate human-readable reason for price suggestion
     */
    private function generateReason(float $suggested, float $current, float $demandFactor): string
    {
        $change = (($suggested - $current) / $current) * 100;

        if ($change > 5) {
            return "High demand detected - " . ($demandFactor > 1.2 ? "very strong" : "good") . " market opportunity";
        } elseif ($change < -5) {
            return "Low demand or high competition - price reduction recommended";
        }

        return "Current price is near optimal market position";
    }

    /**
     * Determine appropriate cache TTL based on volatility
     */
    private function determineCacheTTL(float $demand, float $inventory): int
    {
        // Volatile markets (high demand changes) need shorter cache
        if ($demand > 1.3 || $demand < 0.7) {
            return self::CACHE_TTL_SHORT;  // 5 min
        }

        // High inventory fluctuation = shorter cache
        if ($inventory < 0.85 || $inventory > 1.15) {
            return self::CACHE_TTL_MEDIUM;  // 30 min
        }

        // Stable conditions = longer cache
        return self::CACHE_TTL_LONG;  // 1 hour
    }

    /**
     * Invalidate price suggestion cache for item
     */
    public function invalidateCache(int $itemId, int $tenantId): void
    {
        $cacheKey = "price_suggestion:tenant:{$tenantId}:item:{$itemId}";
        Cache::forget($cacheKey);

        Log::channel('audit')->debug('PriceML: cache invalidated', [
            'tenant_id' => $tenantId,
            'item_id' => $itemId,
        ]);
    }

    /**
     * Get historical price elasticity for item
     * Helps understand how quantity responds to price changes
     */
    public function getElasticity(int $itemId, int $days = 60): array
    {
        $priceChanges = DB::table('price_history')
            ->where('product_id', $itemId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();

        if ($priceChanges->count() < 2) {
            return ['elasticity' => 0, 'confidence' => 0, 'samples' => 0];
        }

        // Calculate elasticity = % change in quantity / % change in price
        $elasticity = 0;
        $count = 0;

        foreach ($priceChanges as $i => $change) {
            if ($i === 0) continue;

            $prevChange = $priceChanges[$i - 1];
            $priceDelta = (($change->price - $prevChange->price) / $prevChange->price) * 100;
            $qtyDelta = (($change->quantity - $prevChange->quantity) / $prevChange->quantity) * 100;

            if ($priceDelta !== 0) {
                $elasticity += $qtyDelta / $priceDelta;
                $count++;
            }
        }

        return [
            'elasticity' => round($count > 0 ? $elasticity / $count : 0, 2),
            'confidence' => round(min(1.0, $count / 10), 2),
            'samples' => $count,
            'period_days' => $days,
        ];
    }
}
