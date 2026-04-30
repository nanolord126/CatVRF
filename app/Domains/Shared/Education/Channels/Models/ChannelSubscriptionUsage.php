<?php

declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Carbon\CarbonImmutable;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class ChannelSubscriptionUsage extends Model
{
    protected $table = 'channel_subscription_usages';

    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'channel_id',
        'plan_id',
        'status',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'amount_paid_kopecks',
        'balance_transaction_id',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'starts_at'           => 'datetime',
        'expires_at'          => 'datetime',
        'cancelled_at'        => 'datetime',
        'amount_paid_kopecks' => 'integer',
        'tags'                => 'json',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(BusinessChannel::class, 'channel_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ChannelSubscriptionPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    /** Scope: только активные */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('expires_at', '>', CarbonImmutable::now());
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
