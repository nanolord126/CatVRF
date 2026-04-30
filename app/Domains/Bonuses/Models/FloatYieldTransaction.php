<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * FloatYieldTransaction - Entity for tracking float yield earnings
 * 
 * Tracks daily yield generated from locked bonus float.
 * Platform earns ~0.06% daily, users earn ~0.012% daily.
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property float $total_float Total locked amount used for calculation
 * @property float $platform_yield Platform's share (~0.06%)
 * @property float $user_yield User's share (~0.012%)
 * @property float $yield_rate Actual rate applied
 * @property \Carbon\Carbon $yield_date Date of yield calculation
 * @property string $correlation_id Unique correlation ID
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class FloatYieldTransaction extends Model
{
    use TenantScoped;

    protected $table = 'float_yield_transactions';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'locked_batch_id',
        'total_float',
        'platform_yield',
        'user_yield',
        'platform_rate',
        'user_rate',
        'yield_rate',
        'yield_date',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'total_float' => 'decimal:2',
        'platform_yield' => 'decimal:4',
        'user_yield' => 'decimal:4',
        'platform_rate' => 'decimal:6',
        'user_rate' => 'decimal:6',
        'yield_rate' => 'decimal:6',
        'yield_date' => 'date',
        'metadata' => 'array',
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

    /** @return BelongsTo<LockedBonusBatch, self> */
    public function lockedBatch(): BelongsTo
    {
        return $this->belongsTo(LockedBonusBatch::class, 'locked_batch_id');
    }

    // Scopes

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate(Builder $query, string $date): Builder
    {
        return $query->where('yield_date', $date);
    }

    public function scopeForDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('yield_date', [$startDate, $endDate]);
    }

    public function scopeForToday(Builder $query): Builder
    {
        return $query->where('yield_date', now()->toDateString());
    }

    // Domain Methods

    public function getTotalYield(): float
    {
        return (float) ($this->platform_yield + $this->user_yield);
    }

    public function getUserYieldPercentage(): float
    {
        if ($this->total_float == 0) {
            return 0.0;
        }

        return ($this->user_yield / $this->total_float) * 100;
    }

    public function getPlatformYieldPercentage(): float
    {
        if ($this->total_float == 0) {
            return 0.0;
        }

        return ($this->platform_yield / $this->total_float) * 100;
    }

    /**
     * Check if yield has been calculated for user on a specific date
     */
    public static function hasYieldForDate(int $userId, int $tenantId, string $date): bool
    {
        return self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('yield_date', $date)
            ->exists();
    }

    /**
     * Get total user yield for a date range
     */
    public static function getTotalUserYield(
        int $userId,
        int $tenantId,
        string $startDate,
        string $endDate
    ): float {
        return (float) self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereBetween('yield_date', [$startDate, $endDate])
            ->sum('user_yield');
    }

    /**
     * Get total platform yield for a date range
     */
    public static function getTotalPlatformYield(
        ?int $tenantId,
        string $startDate,
        string $endDate
    ): float {
        $query = self::whereBetween('yield_date', [$startDate, $endDate]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return (float) $query->sum('platform_yield');
    }

    /**
     * Get total platform float for yield calculation
     */
    public static function getPlatformFloatForYield(?int $tenantId = null): float
    {
        return LockedBonusBatch::getPlatformFloat($tenantId);
    }

    /**
     * Calculate daily yield rates
     */
    public static function calculateYieldRates(): array
    {
        return [
            'platform_rate' => 0.0006, // 0.06% daily (~1.8% monthly)
            'user_rate' => 0.00012, // 0.012% daily (~0.36% monthly)
            'total_rate' => 0.00072, // 0.072% daily (~2.16% monthly)
        ];
    }
}
