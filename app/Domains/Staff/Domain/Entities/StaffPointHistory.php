<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffPointHistory — история изменений очков сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffPointHistory extends Model
{
    protected $table = 'staff_point_history';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'points_change',
        'type',
        'source',
        'description',
        'metadata',
        'balance_after',
    ];

    protected $casts = [
        'points_change' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'json',
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
    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeSpent($query)
    {
        return $query->where('type', 'spent');
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Accessors
    public function getIsPositiveAttribute(): bool
    {
        return $this->points_change > 0;
    }

    public function getIsNegativeAttribute(): bool
    {
        return $this->points_change < 0;
    }
}
