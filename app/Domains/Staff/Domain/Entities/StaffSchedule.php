<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffSchedule — график смен сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'staff_schedules';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'name',
        'type',
        'schedule',
        'valid_from',
        'valid_to',
        'is_active',
    ];

    protected $casts = [
        'schedule' => 'json',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
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

    public function shifts(): HasMany
    {
        return $this->hasMany(StaffShift::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('valid_from', '<=', $now)
                     ->where(function ($q) use ($now) {
                         $q->whereNull('valid_to')->orWhere('valid_to', '>=', $now);
                     });
    }

    // Accessors
    public function getIsValidAttribute(): bool
    {
        $now = now();
        return $this->valid_from->lte($now) && 
               ($this->valid_to === null || $this->valid_to->gte($now));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_to && $this->valid_to->isPast();
    }
}
