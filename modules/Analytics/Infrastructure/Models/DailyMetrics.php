<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily Metrics Model
 *
 * Represents aggregated daily metrics for the entire marketplace.
 * Used for overall KPIs and trends.
 */
final class DailyMetrics extends Model
{
    protected $table = 'analytics_daily_metrics';

    protected $fillable = [
        'tenant_id',
        'date',
        'orders_count',
        'orders_revenue',
        'orders_aov',
        'users_active',
        'users_new',
        'products_viewed',
        'products_added_to_cart',
        'sellers_active',
        'sessions',
        'page_views',
        'gmv',
        'refunds_count',
        'refunds_amount',
        'conversion_rate',
        'cart_abandonment_rate',
        'calculated_at',
    ];

    protected $casts = [
        'date' => 'date',
        'orders_revenue' => 'decimal:2',
        'orders_aov' => 'decimal:2',
        'gmv' => 'decimal:2',
        'refunds_amount' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
        'cart_abandonment_rate' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    /**
     * Get the tenant that owns these metrics.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('tenancy.tenant_model', \App\Models\Tenant::class), 'tenant_id');
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
