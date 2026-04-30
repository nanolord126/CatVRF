<?php

declare(strict_types=1);

namespace App\Models\AML;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Wallet\Transaction;

final class AMLAlert extends Model
{
    protected $fillable = [
        'tenant_id',
        'transaction_id',
        'user_id',
        'alert_type',
        'risk_score',
        'risk_level',
        'details',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'correlation_id',
    ];

    protected $casts = [
        'risk_score' => 'int',
        'details' => 'json',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (app()->bound('tenant') && app('tenant') instanceof \App\Models\Tenant) {
                $query->where('tenant_id', app('tenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', 'high')
            ->orWhere('risk_level', 'critical');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_review';
    }

    public function isCritical(): bool
    {
        return $this->risk_level === 'critical';
    }
}
