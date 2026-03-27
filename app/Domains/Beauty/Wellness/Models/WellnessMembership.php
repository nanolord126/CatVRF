<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Wellness\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * WellnessMembership model - B2B/B2C Membership linking client to center, specialist, and service.
 * Supports recurring or multi-session wellness plans.
 */
final class WellnessMembership extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wellness_memberships';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'center_id',
        'client_id',
        'service_id',
        'plan_type', // annual, monthly, bundle
        'start_at',
        'end_at',
        'is_active',
        'price_per_period',
        'remaining_sessions',
        'benefits_json',
        'correlation_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
        'price_per_period' => 'integer',
        'remaining_sessions' => 'integer',
        'benefits_json' => 'json',
    ];

    /**
     * Boot the model with tenant scoping and record automation.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (string) (tenant()->id ?? 'null');
            $model->correlation_id = $model->correlation_id ?? (string) Str::uuid();
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Relation with the wellness center.
     */
    public function center(): BelongsTo
    {
        return $this->belongsTo(WellnessCenter::class, 'center_id');
    }

    /**
     * Relation with the wellness service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(WellnessService::class, 'service_id');
    }

    /**
     * Active memberships filter.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('end_at', '>', now());
    }

    /**
     * Terminated memberships filter.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('end_at', '<=', now());
    }

    /**
     * Bundle memberships filter.
     */
    public function scopeOfPlanType(Builder $query, string $planType): Builder
    {
        return $query->where('plan_type', $planType);
    }
}
