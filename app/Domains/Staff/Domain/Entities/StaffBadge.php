<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * StaffBadge — бейдж/достижение в системе геймификации.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Категории: achievement, milestone, special
 */
final class StaffBadge extends Model
{
    use SoftDeletes;

    protected $table = 'staff_badges';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'category',
        'requirements',
        'points_reward',
        'total_earned',
    ];

    protected $casts = [
        'requirements' => 'json',
        'points_reward' => 'integer',
        'total_earned' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function achievements(): HasMany
    {
        return $this->hasMany(StaffAchievement::class);
    }

    // Scopes
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('total_earned');
    }

    public function scopeHighReward($query, int $minPoints = 100)
    {
        return $query->where('points_reward', '>=', $minPoints);
    }

    // Accessors
    public function getIconUrlAttribute(): string
    {
        return $this->icon ?? asset('images/badges/default.png');
    }
}
