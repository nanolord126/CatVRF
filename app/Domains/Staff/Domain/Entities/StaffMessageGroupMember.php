<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffMessageGroupMember — участник группы сообщений.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffMessageGroupMember extends Model
{
    protected $table = 'staff_message_group_members';

    protected $fillable = [
        'tenant_id',
        'group_id',
        'staff_id',
        'role',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(StaffMessageGroup::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNull('left_at');
    }

    public function scopeLeft($query)
    {
        return $query->whereNotNull('left_at');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // Accessors
    public function getIsActiveAttribute(): bool
    {
        return $this->left_at === null;
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->role === 'admin';
    }

    public function getIsModeratorAttribute(): bool
    {
        return $this->role === 'moderator';
    }
}
