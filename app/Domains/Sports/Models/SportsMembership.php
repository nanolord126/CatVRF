<?php

namespace App\Domains\Sports\Models;

use App\Traits\Common\HasEcosystemFeatures;
use App\Traits\Common\HasEcosystemAuth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SportsMembership Model - Членство в спортзале/клубе
 * 
 * Production ready model для управления членством в домене Sports.
 * Содержит информацию о членстве, членах и статусе подписки.
 */
class SportsMembership extends Model
{
    use HasEcosystemFeatures, HasEcosystemAuth;

    protected $table = 'sports_memberships';
    protected $guarded = [];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'member_id',
        'tier',
        'monthly_fee',
        'started_at',
        'expires_at',
        'status',
        'auto_renew',
        'correlation_id',
        'metadata',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    public function member(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'member_id');
    }

    // ============================================
    // BUSINESS LOGIC
    // ============================================

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expires_at->isBetween(now(), now()->addDays(7));
    }

    public function canRenew(): bool
    {
        return $this->auto_renew || $this->isExpired();
    }

    public function renew(): bool
    {
        return $this->update([
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);
    }
}
