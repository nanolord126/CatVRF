<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ApprovalRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_id',
        'requester_id',
        'tenant_id',
        'business_group_id',
        'operation_type',
        'operation_data',
        'approval_level',
        'approval_window_seconds',
        'status',
        'approved_at',
        'rejected_at',
        'expired_at',
        'escalated_at',
        'approver_id',
        'approval_reason',
        'rejection_reason',
        'escalation_level',
        'escalated_to_id',
        'executed',
        'executed_at',
        'execution_result',
        'conflicts_detected',
        'has_conflicts',
        'correlation_id',
        'requester_ip',
        'requester_user_agent',
        'expires_at',
    ];

    protected $casts = [
        'operation_data' => 'json',
        'approval_window_seconds' => 'integer',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expired_at' => 'datetime',
        'escalated_at' => 'datetime',
        'escalation_level' => 'integer',
        'executed' => 'boolean',
        'executed_at' => 'datetime',
        'execution_result' => 'json',
        'conflicts_detected' => 'json',
        'has_conflicts' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isEscalated(): bool
    {
        return $this->status === 'escalated' || $this->escalation_level > 0;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function hasConflicts(): bool
    {
        return $this->has_conflicts;
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', CarbonImmutable::now());
            });
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($query) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<=', CarbonImmutable::now());
            });
    }

    public function scopeForRequester($query, int $userId)
    {
        return $query->where('requester_id', $userId);
    }

    public function scopeForApprover($query, int $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('approver_id', $userId)
                ->orWhere('escalated_to_id', $userId);
        });
    }

    public function scopeByOperationType($query, string $operationType)
    {
        return $query->where('operation_type', $operationType);
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
