<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Seller Daily Metrics Model
 *
 * Represents aggregated daily metrics for a specific seller.
 * Used for fast dashboard queries without heavy JOINs.
 */
final class SellerDailyMetrics extends Model
{
    protected $table = 'analytics_seller_metrics';

    protected $fillable = [
        'tenant_id',
        'seller_id',
        'date',
        'orders_count',
        'orders_revenue',
        'orders_aov',
        'products_viewed',
        'products_sold',
        'unique_customers',
        'conversion_rate',
        'refunds_count',
        'refunds_amount',
        'seller_rating',
        'calculated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'orders_revenue' => 'decimal:2',
        'orders_aov' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'refunds_amount' => 'decimal:2',
        'seller_rating' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the seller that owns these metrics.
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
}
