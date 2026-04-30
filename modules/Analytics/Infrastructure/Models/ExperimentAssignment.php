<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Experiment Assignment Model
 *
 * Tracks user assignments to experiment variants.
 * Production-ready with GDPR compliance (soft deletes) and proper relationships.
 */
final class ExperimentAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'experiment_assignments';

    protected $fillable = [
        'tenant_id',
        'experiment_id',
        'variant_id',
        'seller_id',
        'buyer_id',
        'clv_180d_at_assignment',
        'clv_365d_at_assignment',
        'churn_prob_at_assignment',
        'clv_segment_at_assignment',
        'assignment_method',
        'hash_bucket',
        'assigned_at',
        'first_exposed_at',
        'last_exposed_at',
        'exposure_count',
        'revenue_14d',
        'revenue_30d',
        'orders_14d',
        'orders_30d',
        'clv_delta',
        'churn_prob_delta',
    ];

    protected $casts = [
        'clv_180d_at_assignment' => 'decimal:2',
        'clv_365d_at_assignment' => 'decimal:2',
        'churn_prob_at_assignment' => 'float',
        'hash_bucket' => 'integer',
        'assigned_at' => 'datetime',
        'first_exposed_at' => 'datetime',
        'last_exposed_at' => 'datetime',
        'exposure_count' => 'integer',
        'revenue_14d' => 'decimal:2',
        'revenue_30d' => 'decimal:2',
        'orders_14d' => 'integer',
        'orders_30d' => 'integer',
        'clv_delta' => 'float',
        'churn_prob_delta' => 'float',
    ];

    /**
     * Relationship: Assignment belongs to experiment.
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Relationship: Assignment belongs to variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExperimentVariant::class);
    }

    /**
     * Relationship: Assignment belongs to seller.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'seller_id');
    }

    /**
     * Relationship: Assignment belongs to buyer.
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    /**
     * Relationship: Assignment belongs to tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Scope: Filter by experiment.
     */
    public function scopeForExperiment($query, int $experimentId)
    {
        return $query->where('experiment_id', $experimentId);
    }

    /**
     * Scope: Filter by variant.
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope: Filter by seller.
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope: Filter by buyer.
     */
    public function scopeForBuyer($query, int $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope: Filter by tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter by CLV segment at assignment.
     */
    public function scopeForSegment($query, string $segment)
    {
        return $query->where('clv_segment_at_assignment', $segment);
    }

    /**
     * Scope: Filter by assignment date range.
     */
    public function scopeAssignedBetween($query, $start, $end)
    {
        return $query->whereBetween('assigned_at', [$start, $end]);
    }

    /**
     * Check if user has been exposed to variant.
     */
    public function hasBeenExposed(): bool
    {
        return $this->first_exposed_at !== null;
    }

    /**
     * Record exposure.
     */
    public function recordExposure(): void
    {
        $now = now();
        
        $this->update([
            'first_exposed_at' => $this->first_exposed_at ?? $now,
            'last_exposed_at' => $now,
            'exposure_count' => $this->exposure_count + 1,
        ]);
    }

    /**
     * Update post-experiment metrics.
     */
    public function updateMetrics(array $metrics): void
    {
        $this->update(array_intersect_key($metrics, array_flip([
            'revenue_14d',
            'revenue_30d',
            'orders_14d',
            'orders_30d',
            'clv_delta',
            'churn_prob_delta',
        ])));
    }

    /**
     * Find existing assignment for buyer-seller-experiment.
     */
    public static function findExisting(
        int $tenantId,
        int $experimentId,
        int $sellerId,
        int $buyerId
    ): ?self {
        return static::where('tenant_id', $tenantId)
            ->where('experiment_id', $experimentId)
            ->where('seller_id', $sellerId)
            ->where('buyer_id', $buyerId)
            ->first();
    }
}
