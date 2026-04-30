<?php

declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\User;

final class StationerySubscription extends Model
{
    protected $table = 'stationery_subscriptions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'tier',
        'monthly_price_cents',
        'is_active',
        'next_delivery_at',
        'preferences',
        'correlation_id',
    ];

    protected $casts = [
        'preferences' => 'json',
        'is_active' => 'boolean',
        'monthly_price_cents' => 'integer',
        'next_delivery_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = (string) Str::uuid();
            if ($this->guard->check() && empty($model->tenant_id)) {
                $model->tenant_id = $this->guard->user()->tenant_id;
            }
        });

        self::addGlobalScope('tenant', function ($builder) {
            if ($this->guard->check()) {
                $builder->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }
}
