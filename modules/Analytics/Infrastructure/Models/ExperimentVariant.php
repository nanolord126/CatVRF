<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Analytics\Application\DTOs\VariantDTO;

/**
 * Experiment Variant Model
 *
 * Represents a variant (A, B, C, control) in an A/B test.
 * Production-ready with proper relationships and casting.
 */
final class ExperimentVariant extends Model
{
    use HasFactory;

    protected $table = 'experiment_variants';

    protected $fillable = [
        'experiment_id',
        'key',
        'name',
        'configuration',
        'traffic_allocation',
        'is_control',
        'sample_size',
        'metrics',
    ];

    protected $casts = [
        'configuration' => 'array',
        'metrics' => 'array',
        'traffic_allocation' => 'integer',
        'sample_size' => 'integer',
        'is_control' => 'boolean',
    ];

    /**
     * Relationship: Variant belongs to experiment.
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Relationship: Variant has many assignments.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExperimentAssignment::class);
    }

    /**
     * Relationship: Variant has many metrics.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(ExperimentMetric::class);
    }

    /**
     * Scope: Only control variants.
     */
    public function scopeControl($query)
    {
        return $query->where('is_control', true);
    }

    /**
     * Scope: Only treatment variants (not control).
     */
    public function scopeTreatment($query)
    {
        return $query->where('is_control', false);
    }

    /**
     * Get discount from configuration.
     */
    public function getDiscount(): ?int
    {
        return $this->configuration['discount'] ?? null;
    }

    /**
     * Get message from configuration.
     */
    public function getMessage(): ?string
    {
        return $this->configuration['message'] ?? null;
    }

    /**
     * Get coupon code from configuration.
     */
    public function getCouponCode(): ?string
    {
        return $this->configuration['coupon_code'] ?? null;
    }

    /**
     * Increment sample size.
     */
    public function incrementSampleSize(int $count = 1): void
    {
        $this->increment('sample_size', $count);
    }

    /**
     * Convert to DTO.
     */
    public function toDTO(): VariantDTO
    {
        return VariantDTO::fromArray($this->toArray());
    }
}
