<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StaffShiftSwap — подмена смены сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffShiftSwap extends Model
{
    protected $table = 'staff_shift_swaps';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'original_staff_id',
        'replacement_staff_id',
        'shift_id',
        'status',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function originalStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'original_staff_id');
    }

    public function replacementStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'replacement_staff_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(StaffShift::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Accessors
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsRejectedAttribute(): bool
    {
        return $this->status === 'rejected';
    }
}
