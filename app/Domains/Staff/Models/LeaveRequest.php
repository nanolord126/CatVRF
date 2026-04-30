<?php

declare(strict_types=1);

namespace App\Domains\Staff\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class LeaveRequest extends Model
{
    use TenantScoped;

    protected $table = 'leaves';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejection_reason',
        'notes',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'days' => 'integer',
        'tags' => 'array',
        'metadata' => 'array',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'employee_id');
    }

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

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->where('start_date', '>=', $startDate)
            ->where('end_date', '<=', $endDate);
    }

    // Helper aliases for service compatibility
    public function getTypeAttribute()
    {
        return $this->leave_type;
    }

    public function getDaysRequestedAttribute()
    {
        return $this->days;
    }

    public function getApprovalNotesAttribute()
    {
        return $this->notes;
    }

    public function getRejectedAtAttribute()
    {
        return $this->deleted_at; // Use soft delete timestamp for rejection if needed
    }

    public function getCancelledAtAttribute()
    {
        return $this->deleted_at; // Use soft delete timestamp for cancellation
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        self::creating(function ($model) {
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
