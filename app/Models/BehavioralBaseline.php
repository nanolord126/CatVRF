<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Behavioral Baseline Model
 *
 * Stores baseline behavioral patterns for users (typing, mouse, touch, session)
 * Used for continuous authentication anomaly detection
 */
final class BehavioralBaseline extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'baseline_type',
        'baseline_data',
        'sample_count',
    ];

    protected $casts = [
        'baseline_data' => 'array',
        'sample_count' => 'integer',
    ];

    /**
     * Get the user that owns the baseline.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if baseline has enough samples for reliable comparison
     */
    public function hasEnoughSamples(int $minSamples = 20): bool
    {
        return $this->sample_count >= $minSamples;
    }

    /**
     * Get baseline data for a specific feature
     */
    public function getFeature(string $feature): mixed
    {
        return $this->baseline_data[$feature] ?? null;
    }

    /**
     * Scope for specific baseline type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('baseline_type', $type);
    }
}
