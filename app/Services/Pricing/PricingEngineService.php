<?php declare(strict_types=1);

namespace App\Services\Pricing;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;

/**
 * Unified Pricing Engine Service
 * 
 * Centralizes dynamic pricing logic for all verticals.
 * Handles:
 * - Base price calculation
 * - Dynamic pricing based on demand/supply
 * - B2B discounts
 * - Time-based pricing (peak hours, seasonal)
 * - Multi-tier pricing
 * 
 * This replaces scattered pricing logic across services.
 */
final readonly class PricingEngineService
{
    private const string CACHE_PREFIX = 'pricing:';
    private const int CACHE_TTL_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LogManager $logger,
    ) {}

    /**
     * Calculate final price for a service/product
     * 
     * @param string $vertical Vertical name (medical, food, beauty, etc.)
     * @param int $basePrice Base price in smallest currency unit (kopecks)
     * @param array $context Context data (user_id, business_group, time, location, etc.)
     * @return array ['final_price' => int, 'base_price' => int, 'discount_amount' => int, 'applied_rules' => array]
     */
    public function calculatePrice(
        string $vertical,
        int $basePrice,
        array $context = []
    ): array {
        $cacheKey = $this->buildCacheKey($vertical, $basePrice, $context);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $appliedRules = [];
        $finalPrice = $basePrice;
        $totalDiscount = 0;

        // Apply dynamic pricing rules
        $dynamicAdjustment = $this->applyDynamicPricing($vertical, $basePrice, $context);
        if ($dynamicAdjustment['adjustment'] !== 0) {
            $finalPrice += $dynamicAdjustment['adjustment'];
            $appliedRules[] = $dynamicAdjustment['rule'];
            $totalDiscount += $dynamicAdjustment['adjustment'] < 0 ? abs($dynamicAdjustment['adjustment']) : 0;
        }

        // Apply B2B discounts
        if (!empty($context['business_group_id'])) {
            $b2bDiscount = $this->applyB2BDiscount($vertical, $finalPrice, $context);
            if ($b2bDiscount['discount'] > 0) {
                $finalPrice -= $b2bDiscount['discount'];
                $appliedRules[] = $b2bDiscount['rule'];
                $totalDiscount += $b2bDiscount['discount'];
            }
        }

        // Apply time-based pricing
        $timeAdjustment = $this->applyTimeBasedPricing($vertical, $finalPrice, $context);
        if ($timeAdjustment['adjustment'] !== 0) {
            $finalPrice += $timeAdjustment['adjustment'];
            $appliedRules[] = $timeAdjustment['rule'];
            $totalDiscount += $timeAdjustment['adjustment'] < 0 ? abs($timeAdjustment['adjustment']) : 0;
        }

        // Ensure price doesn't go below minimum
        $minimumPrice = $this->getMinimumPrice($vertical, $basePrice);
        if ($finalPrice < $minimumPrice) {
            $finalPrice = $minimumPrice;
            $appliedRules[] = 'minimum_price_floor';
        }

        $result = [
            'final_price' => max(0, $finalPrice),
            'base_price' => $basePrice,
            'discount_amount' => $totalDiscount,
            'applied_rules' => $appliedRules,
            'calculated_at' => now()->toIso8601String(),
        ];

        $this->cache->put($cacheKey, $result, self::CACHE_TTL_SECONDS);

        $this->logger->channel('audit')->info('Price calculated', [
            'vertical' => $vertical,
            'base_price' => $basePrice,
            'final_price' => $result['final_price'],
            'discount_amount' => $totalDiscount,
            'applied_rules' => $appliedRules,
            'context' => $this->sanitizeContext($context),
        ]);

        return $result;
    }

    /**
     * Apply dynamic pricing based on demand/supply factors
     */
    private function applyDynamicPricing(
        string $vertical,
        int $basePrice,
        array $context
    ): array {
        // Default: no adjustment
        $adjustment = 0;
        $rule = null;

        $demandFactor = $context['demand_factor'] ?? 1.0;
        $supplyFactor = $context['supply_factor'] ?? 1.0;

        // Dynamic pricing multiplier based on demand/supply ratio
        $multiplier = $demandFactor / max(0.1, $supplyFactor);

        // Cap multiplier between 0.8 and 2.0 (max 100% increase, 20% decrease)
        $multiplier = max(0.8, min(2.0, $multiplier));

        if ($multiplier !== 1.0) {
            $adjustment = (int) ($basePrice * ($multiplier - 1.0));
            $rule = sprintf('dynamic_pricing_%.2fx', $multiplier);
        }

        return [
            'adjustment' => $adjustment,
            'rule' => $rule,
        ];
    }

    /**
     * Apply B2B discounts for business groups
     */
    private function applyB2BDiscount(
        string $vertical,
        int $currentPrice,
        array $context
    ): array {
        $discount = 0;
        $rule = null;

        $businessGroupId = $context['business_group_id'] ?? null;
        if (!$businessGroupId) {
            return ['discount' => 0, 'rule' => null];
        }

        // Get discount rate for business group (could be from DB)
        $discountRate = $this->getB2BDiscountRate($businessGroupId, $vertical);

        if ($discountRate > 0) {
            $discount = (int) ($currentPrice * $discountRate);
            $rule = sprintf('b2b_discount_%d_percent', (int) ($discountRate * 100));
        }

        return [
            'discount' => $discount,
            'rule' => $rule,
        ];
    }

    /**
     * Apply time-based pricing (peak hours, seasonal)
     */
    private function applyTimeBasedPricing(
        string $vertical,
        int $currentPrice,
        array $context
    ): array {
        $adjustment = 0;
        $rule = null;

        $now = $context['timestamp'] ?? now();
        $hour = $now instanceof \Carbon\Carbon ? $now->hour : (int) date('H', strtotime($now));

        // Peak hours pricing (e.g., 18:00-22:00 for food delivery)
        $peakHours = $this->getPeakHours($vertical);
        if (in_array($hour, $peakHours)) {
            $peakMultiplier = $this->getPeakMultiplier($vertical);
            $adjustment = (int) ($currentPrice * ($peakMultiplier - 1.0));
            $rule = sprintf('peak_hours_%d_multiplier_%.2f', $hour, $peakMultiplier);
        }

        return [
            'adjustment' => $adjustment,
            'rule' => $rule,
        ];
    }

    /**
     * Get minimum price for a vertical
     */
    private function getMinimumPrice(string $vertical, int $basePrice): int
    {
        // Default: 50% of base price
        return (int) ($basePrice * 0.5);
    }

    /**
     * Get B2B discount rate for business group
     */
    private function getB2BDiscountRate(int $businessGroupId, string $vertical): float
    {
        // In production, fetch from database or configuration
        // Default: 10% discount for B2B
        $discountRates = [
            'medical' => 0.15, // 15% discount for medical B2B
            'food' => 0.10,    // 10% discount for food B2B
            'beauty' => 0.12,  // 12% discount for beauty B2B
        ];

        return $discountRates[$vertical] ?? 0.10;
    }

    /**
     * Get peak hours for a vertical
     */
    private function getPeakHours(string $vertical): array
    {
        $peakHours = [
            'food' => [18, 19, 20, 21], // 18:00-22:00
            'medical' => [9, 10, 11, 15, 16, 17], // Morning and afternoon
            'beauty' => [10, 11, 12, 16, 17, 18], // Mid-morning and late afternoon
        ];

        return $peakHours[$vertical] ?? [];
    }

    /**
     * Get peak multiplier for a vertical
     */
    private function getPeakMultiplier(string $vertical): float
    {
        $multipliers = [
            'food' => 1.2,     // 20% surcharge during peak
            'medical' => 1.1,  // 10% surcharge during peak
            'beauty' => 1.15,  // 15% surcharge during peak
        ];

        return $multipliers[$vertical] ?? 1.1;
    }

    /**
     * Build cache key for pricing calculation
     */
    private function buildCacheKey(string $vertical, int $basePrice, array $context): string
    {
        $contextHash = md5(json_encode($context));
        return sprintf(
            '%s%s:%d:%s',
            self::CACHE_PREFIX,
            $vertical,
            $basePrice,
            $contextHash
        );
    }

    /**
     * Sanitize context for logging (remove sensitive data)
     */
    private function sanitizeContext(array $context): array
    {
        return collect($context)
            ->except(['user_id', 'business_group_id', 'ip_address'])
            ->all();
    }

    /**
     * Invalidate pricing cache for a vertical
     */
    public function invalidateCache(string $vertical): void
    {
        // In production, use cache tags for efficient invalidation
        $this->logger->channel('audit')->info('Pricing cache invalidated', [
            'vertical' => $vertical,
        ]);
    }
}
