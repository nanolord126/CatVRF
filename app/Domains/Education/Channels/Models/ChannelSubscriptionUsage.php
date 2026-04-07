<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ChannelSubscriptionUsage extends Model
{
    use HasFactory;

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

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


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
        public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
        {
            return $query->where('status', 'active')->where('expires_at', '>', Carbon::now());
        }
}
