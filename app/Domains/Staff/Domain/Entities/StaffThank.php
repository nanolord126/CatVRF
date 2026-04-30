<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffThank — благодарность и комплименты между сотрудниками.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffThank extends Model
{
    protected $table = 'staff_thanks';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'sender_id',
        'receiver_id',
        'type',
        'message',
        'badge_type',
        'is_public',
        'related_entity_type',
        'related_entity_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'related_entity_id' => 'integer',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'receiver_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getBadgeIconAttribute(): string
    {
        return match($this->badge_type) {
            'quick_helper' => '⚡',
            'team_player' => '🤝',
            'problem_solver' => '💡',
            'mentor' => '🎓',
            'positive' => '😊',
            default => '👍',
        };
    }
}
