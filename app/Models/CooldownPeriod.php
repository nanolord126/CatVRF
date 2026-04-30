<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CooldownActionType;
use App\Enums\CooldownStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cooldown Period Model
 * 
 * Represents a cooling-off period for high-risk actions.
 * Cooldowns can be applied at user or tenant level.
 */
final class CooldownPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tenant_id',
        'action_type',
        'triggered_at',
        'expires_at',
        'reason',
        'status',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'metadata',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'expires_at' => 'datetime',
        'overridden_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationship to the user who triggered the cooldown
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship to the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship to the admin who overrode the cooldown
     */
    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    /**
     * Scope for active cooldowns
     */
    public function scopeActive($query)
    {
        return $query->where('status', CooldownStatus::ACTIVE->value)
            ->where('expires_at', '>', CarbonImmutable::now());
    }

    /**
     * Scope for expired cooldowns
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', CarbonImmutable::now())
            ->where('status', CooldownStatus::ACTIVE->value);
    }

    /**
     * Scope for overridden cooldowns
     */
    public function scopeOverridden($query)
    {
        return $query->where('status', CooldownStatus::OVERRIDDEN->value);
    }

    /**
     * Scope for user cooldowns
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for tenant cooldowns
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for action type
     */
    public function scopeByActionType($query, CooldownActionType $actionType)
    {
        return $query->where('action_type', $actionType->value);
    }

    /**
     * Check if cooldown is currently active
     */
    public function isActive(): bool
    {
        return $this->status === CooldownStatus::ACTIVE->value
            && $this->expires_at->isFuture();
    }

    /**
     * Check if cooldown is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get remaining time in seconds
     */
    public function getRemainingSeconds(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, CarbonImmutable::now()->diffInSeconds($this->expires_at));
    }

    /**
     * Get remaining time in human-readable format
     */
    public function getRemainingTimeForHumans(): string
    {
        $seconds = $this->getRemainingSeconds();
        
        if ($seconds === 0) {
            return 'Истёк';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return "{$hours} ч. {$minutes} мин.";
        }

        return "{$minutes} мин.";
    }

    /**
     * Mark cooldown as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => CooldownStatus::EXPIRED->value,
        ]);
    }

    /**
     * Override cooldown manually
     */
    public function override(int $overriddenBy, string $reason): bool
    {
        return $this->update([
            'status' => CooldownStatus::OVERRIDDEN->value,
            'overridden_by' => $overriddenBy,
            'overridden_at' => CarbonImmutable::now(),
            'override_reason' => $reason,
        ]);
    }

    /**
     * Get action type enum instance
     */
    public function getActionTypeEnum(): CooldownActionType
    {
        return CooldownActionType::from($this->action_type);
    }

    /**
     * Get status enum instance
     */
    public function getStatusEnum(): CooldownStatus
    {
        return CooldownStatus::from($this->status);
    }
}
