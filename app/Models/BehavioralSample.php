<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Behavioral Sample Model
 *
 * Stores individual behavioral samples for analysis and baseline building
 */
final class BehavioralSample extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'session_id',
        'sample_type',
        'sample_data',
        'analyzed_at',
        'anomaly_score',
        'is_anomalous',
    ];

    protected $casts = [
        'sample_data' => 'array',
        'analyzed_at' => 'datetime',
        'anomaly_score' => 'decimal:4',
        'is_anomalous' => 'boolean',
    ];

    /**
     * Get the user that owns the sample.
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
     * Mark sample as analyzed with anomaly score
     */
    public function markAnalyzed(float $score, bool $isAnomalous): void
    {
        $this->update([
            'analyzed_at' => CarbonImmutable::now(),
            'anomaly_score' => $score,
            'is_anomalous' => $isAnomalous,
        ]);
    }

    /**
     * Scope for specific sample type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('sample_type', $type);
    }

    /**
     * Scope for anomalous samples
     */
    public function scopeAnomalous($query)
    {
        return $query->where('is_anomalous', true);
    }

    /**
     * Scope for specific session
     */
    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
