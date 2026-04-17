<?php declare(strict_types=1);

namespace App\Models\Stationery;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

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
            'correlation_id'
        ];

        protected $casts = [
            'preferences' => 'json',
            'is_active' => 'boolean',
            'monthly_price_cents' => 'integer',
            'next_delivery_at' => 'datetime',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                if ($this->guard->check() && empty($model->tenant_id)) {
                    $model->tenant_id = $this->guard->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if ($this->guard->check()) {
                    $builder->where('tenant_id', $this->guard->user()->tenant_id);
                }
            });
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        public function tenant(): BelongsTo
        {
            return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
        }
}
