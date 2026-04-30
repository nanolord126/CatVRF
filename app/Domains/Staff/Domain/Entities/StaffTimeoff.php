<?php

declare(strict_types=1);

namespace App\Domains\Staff\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

/**
 * StaffTimeoff — отпуск или отсутствие сотрудника.
 * CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class StaffTimeoff extends Model
{
    use SoftDeletes;

    protected $table = 'staff_timeoffs';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'staff_id',
        'approved_by',
        'type',
        'status',
        'start_date',
        'end_date',
        'duration_days',
        'reason',
        'notes',
        'approved_at',
        'rejection_reason',
        'attachment_url',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'integer',
        'approved_at' => 'datetime',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
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

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('status', 'approved')
                     ->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'approved')
                     ->where('start_date', '>', now());
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

    public function getIsCurrentAttribute(): bool
    {
        $now = now();
        return $this->is_approved && 
               $this->start_date->lte($now) && 
               $this->end_date->gte($now);
    }

    public function getIsVacationAttribute(): bool
    {
        return $this->type === 'vacation';
    }

    public function getIsSickLeaveAttribute(): bool
    {
        return $this->type === 'sick_leave';
    }
}
