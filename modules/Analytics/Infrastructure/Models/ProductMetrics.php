<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product Metrics Model
 *
 * Represents aggregated daily metrics for a specific product.
 * Used for product analytics, top products lists, and inventory optimization.
 */
final class ProductMetrics extends Model
{
    protected $table = 'analytics_product_metrics';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'seller_id',
        'date',
        'views',
        'add_to_cart',
        'purchases',
        'unique_viewers',
        'revenue',
        'conversion_rate',
        'cart_conversion_rate',
        'refunds',
        'refund_rate',
        'avg_rating',
        'calculated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'revenue' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cart_conversion_rate' => 'decimal:2',
        'refund_rate' => 'decimal:2',
        'avg_rating' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the product that owns these metrics.
     * TODO: Configure actual product model in config/analytics.php
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(config('analytics.models.product', \Modules\Catalog\Models\Product::class), 'product_id');
    }

    /**
     * Get the seller that owns this product's metrics.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(config('analytics.models.seller', \App\Models\User::class), 'seller_id');
    }

    /**
     * Get the tenant that owns these metrics.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model', \App\Models\Tenant::class), 'tenant_id');
    }

    /**
     * Scope for a specific seller.
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope for a specific tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for date range.
     */
    public function scopeForPeriod($query, string $from, string $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }

    /**
     * Scope for top products by revenue.
     */
    public function scopeTopByRevenue($query, int $limit = 10)
    {
        return $query->orderBy('revenue', 'desc')->limit($limit);
    }

    /**
     * Scope for top products by views.
     */
    public function scopeTopByViews($query, int $limit = 10)
    {
        return $query->orderBy('views', 'desc')->limit($limit);
    }
}
