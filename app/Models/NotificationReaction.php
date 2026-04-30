<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperNotificationReaction
 */
final readonly class NotificationReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_log_id',
        'user_id',
        'tenant_id',
        'reaction_type',
        'reaction_metadata',
        'reacted_at',
    ];

    protected $casts = [
        'reaction_metadata' => 'array',
        'reacted_at' => 'datetime',
    ];

    /**
     * Reaction types enum
     */
    public const REACTION_LIKE = 'like';
    public const REACTION_DISLIKE = 'dislike';
    public const REACTION_NEUTRAL = 'neutral';
    public const REACTION_HELPFUL = 'helpful';
    public const REACTION_NOT_HELPFUL = 'not_helpful';
    public const REACTION_REPORTED = 'reported';

    public function notificationLog(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeByReactionType($query, string $reactionType)
    {
        return $query->where('reaction_type', $reactionType);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePositive($query)
    {
        return $query->whereIn('reaction_type', [self::REACTION_LIKE, self::REACTION_HELPFUL]);
    }

    public function scopeNegative($query)
    {
        return $query->whereIn('reaction_type', [self::REACTION_DISLIKE, self::REACTION_NOT_HELPFUL, self::REACTION_REPORTED]);
    }

    public function scopeForPeriod($query, string $from, string $to)
    {
        return $query->whereBetween('reacted_at', [$from, $to]);
    }
}
