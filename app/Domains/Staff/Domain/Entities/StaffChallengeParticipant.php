<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * StaffChallengeParticipant — участие сотрудника в челлендже.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffChallengeParticipant extends Model
{
    protected $table = 'staff_challenge_participants';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'challenge_id',
        'progress',
        'progress_data',
        'status',
        'completed_at',
        'rank',
        'points_earned',
    ];

    protected $casts = [
        'progress' => 'decimal:2',
        'progress_data' => 'json',
        'completed_at' => 'datetime',
        'rank' => 'integer',
        'points_earned' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(StaffChallenge::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeTopRanked($query, int $limit = 10)
    {
        return $query->whereNotNull('rank')
                     ->orderBy('rank')
                     ->limit($limit);
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getProgressPercentageAttribute(): float
    {
        return (float) $this->progress;
    }
}
