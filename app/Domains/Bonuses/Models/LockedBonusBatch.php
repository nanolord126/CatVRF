<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * LockedBonusBatch - Entity for bonus batches with 15-day Smart Hold
 * 
 * Tracks bonus batches that are locked and vest over 15 days with daily unlock.
 * Supports activity acceleration and streak multipliers.
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property float $original_amount Original bonus amount
 * @property float $remaining_locked Amount still locked
 * @property float $daily_unlock_rate Daily unlock percentage (default 0.0667 = 1/15)
 * @property \Carbon\Carbon $vested_until When fully unlocked
 * @property array|null $acceleration_history History of acceleration events
 * @property string $source Bonus source (purchase, referral, quest, etc.)
 * @property string $correlation_id Unique correlation ID
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class LockedBonusBatch extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'locked_bonus_batches';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'original_amount',
        'remaining_locked',
        'daily_unlock_rate',
        'vested_until',
        'acceleration_history',
        'source',
        'correlation_id',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'remaining_locked' => 'decimal:2',
        'daily_unlock_rate' => 'decimal:4',
        'vested_until' => 'date',
        'acceleration_history' => 'array',
    ];

    protected static function booted(): void
    {
        self::creating(static function (self $model): void {
            if (empty($model->correlation_id)) {
                $model->correlation_id = Str::uuid()->toString();
            }
        });
    }

    // Relationships

    /** @return BelongsTo<\App\Models\User, self> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('remaining_locked', '>', 0)
            ->where('vested_until', '>=', now()->toDateString());
    }

    public function scopeFullyVested(Builder $query): Builder
    {
        return $query->where('remaining_locked', '<=', 0)
            ->orWhere('vested_until', '<', now()->toDateString());
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeVestingSoon(Builder $query, int $days = 3): Builder
    {
        return $query->whereBetween('vested_until', [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ]);
    }

    // Domain Methods

    public function getUnlockedAmount(): float
    {
        return (float) ($this->original_amount - $this->remaining_locked);
    }

    public function getUnlockPercentage(): float
    {
        if ($this->original_amount == 0) {
            return 100.0;
        }

        return ($this->getUnlockedAmount() / $this->original_amount) * 100;
    }

    public function isFullyVested(): bool
    {
        return $this->remaining_locked <= 0
            || $this->vested_until->isPast();
    }

    public function getDaysRemaining(): int
    {
        return max(0, now()->diffInDays($this->vested_until, false));
    }

    public function unlockDaily(): float
    {
        if ($this->isFullyVested()) {
            return 0.0;
        }

        $dailyAmount = (float) ($this->original_amount * $this->daily_unlock_rate);
        $actualUnlock = min($dailyAmount, $this->remaining_locked);

        $this->remaining_locked -= $actualUnlock;
        $this->save();

        return $actualUnlock;
    }

    public function accelerate(int $days): void
    {
        if ($days <= 0 || $this->isFullyVested()) {
            return;
        }

        $newVestedDate = $this->vested_until->subDays($days);
        
        if ($newVestedDate->isPast()) {
            $newVestedDate = now();
        }

        $this->vested_until = $newVestedDate;

        $history = $this->acceleration_history ?? [];
        $history[] = [
            'accelerated_by_days' => $days,
            'previous_vested_until' => $this->vested_until->addDays($days)->toDateString(),
            'new_vested_until' => $newVestedDate->toDateString(),
            'accelerated_at' => now()->toIso8601String(),
        ];

        $this->acceleration_history = $history;
        $this->save();
    }

    public function getAccelerationDays(): int
    {
        $history = $this->acceleration_history ?? [];
        $totalDays = 0;

        foreach ($history as $event) {
            $totalDays += $event['accelerated_by_days'] ?? 0;
        }

        return $totalDays;
    }

    public function instantUnlock(): float
    {
        $amountToUnlock = $this->remaining_locked;
        $this->remaining_locked = 0;
        $this->vested_until = now();
        $this->save();

        return $amountToUnlock;
    }

    /**
     * Get total locked amount for a user
     */
    public static function getTotalLockedForUser(int $userId, int $tenantId): float
    {
        return (float) self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->active()
            ->sum('remaining_locked');
    }

    /**
     * Get total unlocked amount for a user
     */
    public static function getTotalUnlockedForUser(int $userId, int $tenantId): float
    {
        $batches = self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get();

        $totalUnlocked = 0.0;
        foreach ($batches as $batch) {
            $totalUnlocked += $batch->getUnlockedAmount();
        }

        return $totalUnlocked;
    }

    /**
     * Get platform-wide float (total locked across all users)
     */
    public static function getPlatformFloat(?int $tenantId = null): float
    {
        $query = self::active();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return (float) $query->sum('remaining_locked');
    }
}
