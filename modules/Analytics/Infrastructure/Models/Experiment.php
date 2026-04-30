<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Analytics\Application\DTOs\ExperimentDTO;

/**
 * Experiment Model
 *
 * Represents an A/B test experiment for CLV-based promotions.
 * Production-ready with proper relationships, scopes, and casting.
 */
final class Experiment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'experiments';

    protected $fillable = [
        'tenant_id',
        'seller_id',
        'key',
        'name',
        'description',
        'target_segment',
        'clv_filters',
        'traffic_percent',
        'started_at',
        'ended_at',
        'scheduled_start_at',
        'scheduled_end_at',
        'status',
        'primary_metric',
        'secondary_metrics',
        'results',
        'winning_variant_id',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'clv_filters' => 'array',
        'secondary_metrics' => 'array',
        'results' => 'array',
        'metadata' => 'array',
        'traffic_percent' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'scheduled_start_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
    ];

    /**
     * Relationship: Experiment has many variants.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ExperimentVariant::class);
    }

    /**
     * Relationship: Experiment has many assignments.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class);
    }

    /**
     * Relationship: Experiment has many metrics.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ExperimentMetric::class);
    }

    /**
     * Relationship: Experiment belongs to seller.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'seller_id');
    }

    /**
     * Relationship: Experiment belongs to tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Relationship: Experiment belongs to creator.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only running experiments.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Only draft experiments.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope: Only finished experiments.
     */
    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    /**
     * Scope: Filter by seller.
     */
    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Scope: Filter by tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter by target segment.
     */
    public function scopeForSegment($query, string $segment)
    {
        return $query->where('target_segment', $segment);
    }

    /**
     * Scope: Platform-wide experiments (no seller).
     */
    public function scopePlatformWide($query)
    {
        return $query->whereNull('seller_id');
    }

    /**
     * Find experiment by key.
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Check if experiment is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running' 
            && $this->started_at?->isPast() 
            && (!$this->ended_at || $this->ended_at->isFuture());
    }

    /**
     * Check if experiment is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if experiment is finished.
     */
    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * Get control variant.
     */
    public function getControlVariant(): ?ExperimentVariant
    {
        return $this->variants()->where('is_control', true)->first();
    }

    /**
     * Get variant for traffic bucket (hash-based assignment).
     */
    public function getVariantForBucket(int $bucket): ?ExperimentVariant
    {
        $variants = $this->variants()->orderBy('id')->get();
        $totalAllocation = $variants->sum('traffic_allocation');

        if ($totalAllocation === 0) {
            return $this->getControlVariant();
        }

        $cumulative = 0;
        foreach ($variants as $variant) {
            $cumulative += $variant->traffic_allocation;
            if ($bucket < $cumulative) {
                return $variant;
            }
        }

        return $this->getControlVariant();
    }

    /**
     * Convert to DTO.
     */
    public function toDTO(): ExperimentDTO
    {
        return ExperimentDTO::fromArray($this->toArray());
    }

    /**
     * Start the experiment.
     */
    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Pause the experiment.
     */
    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Finish the experiment.
     */
    public function finish(): void
    {
        $this->update([
            'status' => 'finished',
            'ended_at' => now(),
        ]);
    }

    /**
     * Get total sample size across all variants.
     */
    public function getTotalSampleSize(): int
    {
        return $this->variants->sum('sample_size');
    }
}
