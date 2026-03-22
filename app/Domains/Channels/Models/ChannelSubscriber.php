<?php declare(strict_types=1);

namespace App\Domains\Channels\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Подписчик бизнес-канала.
 *
 * Число подписчиков видит только владелец бизнеса.
 * unsubscribed_at — soft-отписка (не удаляем, для аналитики).
 */
final class ChannelSubscriber extends Model
{
    protected $table = 'channel_subscribers';

    protected $fillable = [
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
}
