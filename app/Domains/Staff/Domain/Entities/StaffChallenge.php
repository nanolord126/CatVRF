<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffChallenge — челлендж/конкурс в системе геймификации.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Типы: daily, weekly, monthly, custom
 * Категории: performance, sales, teamwork, learning
 */
final class StaffChallenge extends Model
{
    use SoftDeletes;

    protected $table = 'staff_challenges';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'title',
        'description',
        'type',
        'category',
        'starts_at',
        'ends_at',
        'requirements',
        'points_reward',
        'badge_id',
        'status',
        'participants_count',
        'completions_count',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'requirements' => 'json',
        'points_reward' => 'integer',
        'badge_id' => 'integer',
        'participants_count' => 'integer',
        'completions_count' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function participants(): HasMany
    {
        return $this->hasMany(StaffChallengeParticipant::class);
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
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('starts_at', '<=', now())
                     ->where(function ($q) {
                         $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
                     });
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' &&
               $this->starts_at->lte(now()) &&
               ($this->ends_at === null || $this->ends_at->gt(now()));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    public function getCompletionRateAttribute(): float
    {
        return $this->participants_count > 0 
            ? ($this->completions_count / $this->participants_count) * 100 
            : 0;
    }

    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->ends_at) {
            return null;
        }

        return $this->ends_at->diffForHumans();
    }
}
