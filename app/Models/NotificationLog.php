<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final readonly class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'channel',
        'event_type',
        'entity_type',
        'entity_id',
        'recipient',
        'status',
        'message_content',
        'error_message',
        'metadata',
        'sent_at',
        'delivered_at',
        'reaction_type',
        'reacted_at',
        'reaction_metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'reaction_metadata' => 'array',
        'reacted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', ['sent', 'delivered']);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'bounced']);
    }

    public function scopeForPeriod($query, string $from, string $to)
    {

    public function scopeWithReaction($query)
    {
        return $query->whereNotNull('reaction_type');
    }

    public function scopeByReactionType($query, string $reactionType)
    {
        return $query->where('reaction_type', $reactionType);
    }

    public function reaction(): HasOne
    {
        return $this->hasOne(NotificationReaction::class);
    }
}
