<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffSkill — навык сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Категории: technical, soft, leadership, domain, etc.
 */
final class StaffSkill extends Model
{
    use SoftDeletes;

    protected $table = 'staff_skills';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'name',
        'category',
        'proficiency_level',
        'years_of_experience',
        'is_certified',
        'certification_name',
        'certification_date',
        'certification_expiry',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'proficiency_level' => 'integer',
        'years_of_experience' => 'integer',
        'is_certified' => 'boolean',
        'certification_date' => 'date',
        'certification_expiry' => 'date',
        'metadata' => 'json',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeCertified($query)
    {
        return $query->where('is_certified', true);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('certification_expiry', '<=', now()->addDays($days))
                     ->where('certification_expiry', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('certification_expiry', '<', now());
    }

    public function scopeAdvanced($query)
    {
        return $query->where('proficiency_level', '>=', 80);
    }

    // Accessors
    public function getIsExpiringAttribute(): bool
    {
        return $this->certification_expiry && 
               $this->certification_expiry->lte(now()->addDays(30)) && 
               $this->certification_expiry->gt(now());
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->certification_expiry && $this->certification_expiry->isPast();
    }

    public function getProficiencyLabelAttribute(): string
    {
        return match(true) {
            $this->proficiency_level >= 90 => 'Expert',
            $this->proficiency_level >= 75 => 'Advanced',
            $this->proficiency_level >= 50 => 'Intermediate',
            $this->proficiency_level >= 25 => 'Beginner',
            default => 'Novice',
        };
    }
}
