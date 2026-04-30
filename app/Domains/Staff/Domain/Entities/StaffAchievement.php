<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffAchievement — полученное сотрудником достижение.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffAchievement extends Model
{
    protected $table = 'staff_achievements';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'badge_id',
        'earned_at',
        'context',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'context' => 'json',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function badge(): BelongsTo
    {
        return $this->belongsTo(StaffBadge::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeRecentlyEarned($query, int $days = 7)
    {
        return $query->where('earned_at', '>=', now()->subDays($days));
    }

    public function scopeByBadge($query, int $badgeId)
    {
        return $query->where('badge_id', $badgeId);
    }

    // Accessors
    public function getDaysSinceEarnedAttribute(): int
    {
        return $this->earned_at->diffInDays(now());
    }
}
