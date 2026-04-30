<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Buyer Seller Features Model
 * 
 * Represents feature store for CLV prediction.
 * Contains aggregated features for buyer-seller pairs.
 * 
 * Production-ready: strict typing, soft deletes, indexed queries.
 */
final class BuyerSellerFeatures extends Model
{
    use SoftDeletes;

    protected $table = 'buyer_seller_features';

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'tenant_id',
        'r_score',
        'f_score',
        'm_score',
        'recency_days',
        'last_purchase_at',
        'frequency_90d',
        'frequency_180d',
        'frequency_365d',
        'monetary_90d',
        'monetary_180d',
        'monetary_365d',
        'avg_order_value',
        'first_purchase_at',
        'days_since_first_purchase',
        'total_orders_all_time',
        'total_monetary_all_time',
        'return_rate',
        'review_score',
        'total_reviews',
        'traffic_search_pct',
        'traffic_recommendation_pct',
        'traffic_direct_pct',
        'traffic_other_pct',
        'last_category',
        'geo_region',
        'geo_city',
        'predicted_clv_180d',
        'predicted_clv_365d',
        'churn_probability',
        'prediction_confidence',
        'clv_segment',
        'actual_monetary_180d',
        'actual_monetary_365d',
        'churned_180d',
        'model_version',
        'features_raw',
    ];

    protected $casts = [
        'last_purchase_at' => 'datetime',
        'first_purchase_at' => 'datetime',
        'monetary_90d' => 'decimal:2',
        'monetary_180d' => 'decimal:2',
        'monetary_365d' => 'decimal:2',
        'avg_order_value' => 'decimal:2',
        'total_monetary_all_time' => 'decimal:2',
        'return_rate' => 'decimal:2',
        'review_score' => 'decimal:2',
        'traffic_search_pct' => 'decimal:2',
        'traffic_recommendation_pct' => 'decimal:2',
        'traffic_direct_pct' => 'decimal:2',
        'traffic_other_pct' => 'decimal:2',
        'predicted_clv_180d' => 'decimal:2',
        'predicted_clv_365d' => 'decimal:2',
        'churn_probability' => 'decimal:4',
        'prediction_confidence' => 'decimal:4',
        'actual_monetary_180d' => 'decimal:2',
        'actual_monetary_365d' => 'decimal:2',
        'churned_180d' => 'boolean',
        'features_raw' => 'array',
    ];

    /**
     * Scope for seller.
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope for buyer.
     */
    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope for tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for segment.
     */
    public function scopeForSegment($query, string $segment)
    {
        return $query->where('clv_segment', $segment);
    }

    /**
     * Scope for high churn risk.
     */
    public function scopeHighChurnRisk($query, float $threshold = 0.5)
    {
        return $query->where('churn_probability', '>', $threshold);
    }

    /**
     * Scope ordered by CLV descending.
     */
    public function scopeOrderByClv($query)
    {
        return $query->orderBy('predicted_clv_180d', 'desc');
    }

    /**
     * Scope for training data (has actual labels).
     */
    public function scopeTrainingData($query)
    {
        return $query->whereNotNull('actual_monetary_180d');
    }

    /**
     * Scope for inference data (no actual labels).
     */
    public function scopeInferenceData($query)
    {
        return $query->whereNull('actual_monetary_180d');
    }

    /**
     * Get buyer relationship.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    /**
     * Get seller relationship.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'seller_id');
    }

    /**
     * Check if has prediction.
     */
    public function hasPrediction(): bool
    {
        return $this->predicted_clv_180d !== null;
    }

    /**
     * Check if is VIP segment.
     */
    public function isVip(): bool
    {
        return $this->clv_segment === 'vip';
    }

    /**
     * Check if high churn risk.
     */
    public function isHighChurnRisk(): bool
    {
        return ($this->churn_probability ?? 0) > 0.5;
    }
}
