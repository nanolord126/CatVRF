<?php declare(strict_types=1);

namespace App\Domains\Education\Channels\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ChannelSubscriber extends Model
{

    protected $table = 'channel_subscribers';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'channel_id',
            'user_id',
            'visibility_preference',
            'correlation_id',
            'subscribed_at',
            'unsubscribed_at',
        ];

        protected $casts = [
            'subscribed_at'   => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];

        public function channel(): BelongsTo
        {
            return $this->belongsTo(BusinessChannel::class, 'channel_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class, 'user_id');
        }

        public function isActive(): bool
        {
            return $this->unsubscribed_at === null;
        }

        /** Scope: только активные подписки */
        public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
        {
            return $query->whereNull('unsubscribed_at');
        }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}