<?php

declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Models\User;

final class PropertyViewing extends Model
{
    protected $table = 'property_viewings';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'property_id',
        'user_id',
        'agent_id',
        'scheduled_at',
        'held_at',
        'hold_expires_at',
        'completed_at',
        'cancelled_at',
        'status',
        'is_b2b',
        'webrtc_room_id',
        'faceid_verified',
        'cancellation_reason',
        'correlation_id',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'held_at' => 'datetime',
        'hold_expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_b2b' => 'boolean',
        'faceid_verified' => 'boolean',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (app()->bound('tenant') && app('tenant') instanceof Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });

        static::creating(function (Model $model): void {
            if (!$model->uuid) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
            }
        });

        static::created(function (PropertyViewing $viewing): void {
            \Log::channel('audit')->info('Property viewing created', [
                'viewing_id' => $viewing->id,
                'property_id' => $viewing->property_id,
                'user_id' => $viewing->user_id,
                'scheduled_at' => $viewing->scheduled_at,
                'is_b2b' => $viewing->is_b2b,
                'correlation_id' => $viewing->correlation_id,
            ]);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Domains\RealEstate\RealEstateAgent::class, 'agent_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'held', 'confirmed']);
    }

    public function scopeHeld(Builder $query): Builder
    {
        return $query->where('status', 'held')
            ->where('hold_expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'held')
            ->where('hold_expires_at', '<=', now());
    }

    public function scopeB2C(Builder $query): Builder
    {
        return $query->where('is_b2b', false);
    }

    public function scopeB2B(Builder $query): Builder
    {
        return $query->where('is_b2b', true);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeScheduledBetween(Builder $query, $start, $end): Builder
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    public function isExpired(): bool
    {
        return $this->status === 'held' && $this->hold_expires_at && $this->hold_expires_at->isPast();
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}
