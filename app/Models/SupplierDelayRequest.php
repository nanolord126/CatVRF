<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierDelayRequest — Запрос поставщика на отсрочку платежа
 * 
 * Поставщики могут запрашивать отсрочку платежа для бизнеса.
 * - До 14 дней: может инициализировать сам поставщик
 * - Более 14 дней: только через систему по запросу в поддержку
 */
final class SupplierDelayRequest extends Model
{
    protected $table = 'supplier_delay_requests';
    
    protected $fillable = [
        'supplier_id',
        'tenant_id',
        'inn',
        'requested_by_user_id',
        'requested_delay_days',
        'reason',
        'status',
        'approved_by_user_id',
        'admin_notes',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const MAX_SELF_INITIATED_DELAY_DAYS = 14;

    /**
     * Get the supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'supplier_id');
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /**
     * Get the user who requested
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'requested_by_user_id');
    }

    /**
     * Get the admin who approved
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by_user_id');
    }

    /**
     * Check if delay exceeds self-initiated limit
     */
    public function exceedsSelfInitiatedLimit(): bool
    {
        return $this->requested_delay_days > self::MAX_SELF_INITIATED_DELAY_DAYS;
    }

    /**
     * Check if request is still valid (not expired)
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_APPROVED 
            && (!$this->expires_at || $this->expires_at->isFuture());
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for valid (approved and not expired)
     */
    public function scopeValid($query)
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }
}
