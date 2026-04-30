<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * StaffPoint — очки и уровень сотрудника в системе геймификации.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffPoint extends Model
{
    protected $table = 'staff_points';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'total_points',
        'available_points',
        'spent_points',
        'level',
        'xp',
        'xp_to_next_level',
        'challenges_completed',
        'badges_earned',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'available_points' => 'integer',
        'spent_points' => 'integer',
        'level' => 'integer',
        'xp' => 'integer',
        'xp_to_next_level' => 'integer',
        'challenges_completed' => 'integer',
        'badges_earned' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(StaffPointHistory::class);
    }

    // Scopes
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopeTopLevel($query, int $limit = 10)
    {
        return $query->orderByDesc('level')
                     ->orderByDesc('xp')
                     ->limit($limit);
    }

    public function scopeMostPoints($query, int $limit = 10)
    {
        return $query->orderByDesc('total_points')
                     ->limit($limit);
    }

    // Accessors
    public function getXpProgressAttribute(): float
    {
        return $this->xp_to_next_level > 0 
            ? ($this->xp / $this->xp_to_next_level) * 100 
            : 0;
    }

    public function getLevelTitleAttribute(): string
    {
        return match(true) {
            $this->level >= 50 => 'Legend',
            $this->level >= 40 => 'Master',
            $this->level >= 30 => 'Expert',
            $this->level >= 20 => 'Senior',
            $this->level >= 10 => 'Intermediate',
            $this->level >= 5 => 'Junior',
            default => 'Novice',
        };
    }

    // Methods
    public function addPoints(int $points, string $source, string $description = null): void
    {
        $this->increment('total_points', $points);
        $this->increment('available_points', $points);
        $this->increment('xp', $points);

        $this->checkLevelUp();
    }

    public function spendPoints(int $points): bool
    {
        if ($this->available_points < $points) {
            return false;
        }

        $this->decrement('available_points', $points);
        $this->increment('spent_points', $points);

        return true;
    }

    protected function checkLevelUp(): void
    {
        while ($this->xp >= $this->xp_to_next_level) {
            $this->xp -= $this->xp_to_next_level;
            $this->level++;
            $this->xp_to_next_level = $this->calculateXpForNextLevel();
        }
    }

    protected function calculateXpForNextLevel(): int
    {
        return (int) (100 * pow(1.2, $this->level));
    }
}
