<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\DTOs;

/**
 * Buyer Features Data Transfer Object
 * 
 * Immutable DTO containing all features for ML model inference.
 * Represents the feature vector for a buyer-seller pair.
 * 
 * Production-ready: readonly properties, strict typing, validation.
 */
final readonly class BuyerFeaturesDTO
{
    public function __construct(
        public int $buyerId,
        public int $sellerId,
        public int $tenantId,
        
        // RFM scores (1-5)
        public ?int $rScore,
        public ?int $fScore,
        public ?int $mScore,
        
        // Recency metrics
        public ?int $recencyDays,
        public ?\DateTimeImmutable $lastPurchaseAt,
        
        // Frequency metrics
        public int $frequency90d,
        public int $frequency180d,
        public int $frequency365d,
        
        // Monetary metrics
        public float $monetary90d,
        public float $monetary180d,
        public float $monetary365d,
        public float $avgOrderValue,
        
        // Lifetime metrics
        public ?\DateTimeImmutable $firstPurchaseAt,
        public ?int $daysSinceFirstPurchase,
        public int $totalOrdersAllTime,
        public float $totalMonetaryAllTime,
        
        // Behavioral metrics
        public float $returnRate,
        public ?float $reviewScore,
        public int $totalReviews,
        
        // Traffic sources (percentages)
        public float $trafficSearchPct,
        public float $trafficRecommendationPct,
        public float $trafficDirectPct,
        public float $trafficOtherPct,
        
        // Category and geography
        public ?string $lastCategory,
        public ?string $geoRegion,
        public ?string $geoCity,
        
        // Training targets (only for historical data)
        public ?float $actualMonetary180d = null,
        public ?float $actualMonetary365d = null,
        public ?bool $churned180d = null,
    ) {}

    /**
     * Create features from database row.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            buyerId: (int) $data['buyer_id'],
            sellerId: (int) $data['seller_id'],
            tenantId: (int) $data['tenant_id'],
            
            rScore: isset($data['r_score']) ? (int) $data['r_score'] : null,
            fScore: isset($data['f_score']) ? (int) $data['f_score'] : null,
            mScore: isset($data['m_score']) ? (int) $data['m_score'] : null,
            
            recencyDays: isset($data['recency_days']) ? (int) $data['recency_days'] : null,
            lastPurchaseAt: isset($data['last_purchase_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['last_purchase_at']) 
                : null,
            
            frequency90d: (int) ($data['frequency_90d'] ?? 0),
            frequency180d: (int) ($data['frequency_180d'] ?? 0),
            frequency365d: (int) ($data['frequency_365d'] ?? 0),
            
            monetary90d: (float) ($data['monetary_90d'] ?? 0),
            monetary180d: (float) ($data['monetary_180d'] ?? 0),
            monetary365d: (float) ($data['monetary_365d'] ?? 0),
            avgOrderValue: (float) ($data['avg_order_value'] ?? 0),
            
            firstPurchaseAt: isset($data['first_purchase_at']) 
                ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $data['first_purchase_at']) 
                : null,
            daysSinceFirstPurchase: isset($data['days_since_first_purchase']) 
                ? (int) $data['days_since_first_purchase'] 
                : null,
            totalOrdersAllTime: (int) ($data['total_orders_all_time'] ?? 0),
            totalMonetaryAllTime: (float) ($data['total_monetary_all_time'] ?? 0),
            
            returnRate: (float) ($data['return_rate'] ?? 0),
            reviewScore: isset($data['review_score']) ? (float) $data['review_score'] : null,
            totalReviews: (int) ($data['total_reviews'] ?? 0),
            
            trafficSearchPct: (float) ($data['traffic_search_pct'] ?? 0),
            trafficRecommendationPct: (float) ($data['traffic_recommendation_pct'] ?? 0),
            trafficDirectPct: (float) ($data['traffic_direct_pct'] ?? 0),
            trafficOtherPct: (float) ($data['traffic_other_pct'] ?? 0),
            
            lastCategory: $data['last_category'] ?? null,
            geoRegion: $data['geo_region'] ?? null,
            geoCity: $data['geo_city'] ?? null,
            
            actualMonetary180d: isset($data['actual_monetary_180d']) ? (float) $data['actual_monetary_180d'] : null,
            actualMonetary365d: isset($data['actual_monetary_365d']) ? (float) $data['actual_monetary_365d'] : null,
            churned180d: isset($data['churned_180d']) ? (bool) $data['churned_180d'] : null,
        );
    }

    /**
     * Convert to feature array for ML model inference.
     * Returns only numeric features in the order expected by the model.
     */
    public function toFeatureArray(): array
    {
        return [
            // RFM scores
            'r_score' => $this->rScore ?? 0,
            'f_score' => $this->fScore ?? 0,
            'm_score' => $this->mScore ?? 0,
            
            // Recency
            'recency_days' => $this->recencyDays ?? 365,
            
            // Frequency
            'frequency_90d' => $this->frequency90d,
            'frequency_180d' => $this->frequency180d,
            'frequency_365d' => $this->frequency365d,
            
            // Monetary
            'monetary_90d' => $this->monetary90d,
            'monetary_180d' => $this->monetary180d,
            'monetary_365d' => $this->monetary365d,
            'avg_order_value' => $this->avgOrderValue,
            
            // Lifetime
            'days_since_first_purchase' => $this->daysSinceFirstPurchase ?? 365,
            'total_orders_all_time' => $this->totalOrdersAllTime,
            'total_monetary_all_time' => $this->totalMonetaryAllTime,
            
            // Behavioral
            'return_rate' => $this->returnRate,
            'review_score' => $this->reviewScore ?? 3.0,
            'total_reviews' => $this->totalReviews,
            
            // Traffic sources
            'traffic_search_pct' => $this->trafficSearchPct,
            'traffic_recommendation_pct' => $this->trafficRecommendationPct,
            'traffic_direct_pct' => $this->trafficDirectPct,
            'traffic_other_pct' => $this->trafficOtherPct,
        ];
    }

    /**
     * Convert to array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'buyer_id' => $this->buyerId,
            'seller_id' => $this->sellerId,
            'tenant_id' => $this->tenantId,
            
            'r_score' => $this->rScore,
            'f_score' => $this->fScore,
            'm_score' => $this->mScore,
            
            'recency_days' => $this->recencyDays,
            'last_purchase_at' => $this->lastPurchaseAt?->format('Y-m-d H:i:s'),
            
            'frequency_90d' => $this->frequency90d,
            'frequency_180d' => $this->frequency180d,
            'frequency_365d' => $this->frequency365d,
            
            'monetary_90d' => $this->monetary90d,
            'monetary_180d' => $this->monetary180d,
            'monetary_365d' => $this->monetary365d,
            'avg_order_value' => $this->avgOrderValue,
            
            'first_purchase_at' => $this->firstPurchaseAt?->format('Y-m-d H:i:s'),
            'days_since_first_purchase' => $this->daysSinceFirstPurchase,
            'total_orders_all_time' => $this->totalOrdersAllTime,
            'total_monetary_all_time' => $this->totalMonetaryAllTime,
            
            'return_rate' => $this->returnRate,
            'review_score' => $this->reviewScore,
            'total_reviews' => $this->totalReviews,
            
            'traffic_search_pct' => $this->trafficSearchPct,
            'traffic_recommendation_pct' => $this->trafficRecommendationPct,
            'traffic_direct_pct' => $this->trafficDirectPct,
            'traffic_other_pct' => $this->trafficOtherPct,
            
            'last_category' => $this->lastCategory,
            'geo_region' => $this->geoRegion,
            'geo_city' => $this->geoCity,
            
            'actual_monetary_180d' => $this->actualMonetary180d,
            'actual_monetary_365d' => $this->actualMonetary365d,
            'churned_180d' => $this->churned180d,
        ];
    }

    /**
     * Check if this is training data (has actual labels).
     */
    public function isTrainingData(): bool
    {
        return $this->actualMonetary180d !== null || $this->churned180d !== null;
    }

    /**
     * Check if buyer is new (less than 30 days since first purchase).
     */
    public function isNewBuyer(): bool
    {
        return ($this->daysSinceFirstPurchase ?? 365) < 30;
    }

    /**
     * Check if buyer is active (purchase in last 90 days).
     */
    public function isActive(): bool
    {
        return ($this->recencyDays ?? 365) < 90;
    }
}
