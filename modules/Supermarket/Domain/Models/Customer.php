<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

/**
 * Customer — Доменная модель клиента супермаркета
 * 
 * Содержит профиль клиента, лояльность, предпочтения
 */
final class Customer extends Model
{
    protected $table = 'supermarket_customers';

    protected $fillable = [
        'uuid',
        'user_id',
        'tenant_id',
        'loyalty_tier',
        'loyalty_points',
        'cashback_balance',
        'total_spent',
        'total_orders',
        'last_order_at',
        'preferences',
        'dietary_restrictions',
        'allergens',
        'rfm_score',
        'rfm_segment',
        'lifetime_value',
        'average_order_value',
        'churn_probability',
        'is_active',
    ];

    protected $casts = [
        'uuid' => 'string',
        'loyalty_points' => 'integer',
        'cashback_balance' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_orders' => 'integer',
        'last_order_at' => 'datetime',
        'preferences' => 'array',
        'dietary_restrictions' => 'array',
        'allergens' => 'array',
        'rfm_score' => 'integer',
        'lifetime_value' => 'decimal:2',
        'average_order_value' => 'decimal:2',
        'churn_probability' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    // Loyalty tiers
    public const TIER_BRONZE = 'bronze';
    public const TIER_SILVER = 'silver';
    public const TIER_GOLD = 'gold';
    public const TIER_PLATINUM = 'platinum';

    // RFM Segments
    public const SEGMENT_CHAMPIONS = 'champions';
    public const SEGMENT_LOYAL = 'loyal';
    public const SEGMENT_POTENTIAL = 'potential';
    public const SEGMENT_NEW = 'new';
    public const SEGMENT_AT_RISK = 'at_risk';
    public const SEGMENT_HIBERNATING = 'hibernating';
    public const SEGMENT_LOST = 'lost';

    /**
     * Отношения
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Вычислить RFM сегмент
     */
    public function calculateRFMSegment(): string
    {
        $recency = $this->last_order_at 
            ? now()->diffInDays($this->last_order_at)
            : 999;
        
        $frequency = $this->total_orders;
        $monetary = (float) $this->total_spent;

        // R score (1-5, 5 = recent)
        $rScore = match(true) {
            $recency <= 30 => 5,
            $recency <= 60 => 4,
            $recency <= 90 => 3,
            $recency <= 180 => 2,
            default => 1,
        };

        // F score (1-5, 5 = frequent)
        $fScore = match(true) {
            $frequency >= 20 => 5,
            $frequency >= 10 => 4,
            $frequency >= 5 => 3,
            $frequency >= 2 => 2,
            default => 1,
        };

        // M score (1-5, 5 = high value)
        $mScore = match(true) {
            $monetary >= 50000 => 5,
            $monetary >= 20000 => 4,
            $monetary >= 10000 => 3,
            $monetary >= 5000 => 2,
            default => 1,
        };

        $this->rfm_score = $rScore * 100 + $fScore * 10 + $mScore;

        // Сегментация
        return match(true) {
            $rScore >= 4 && $fScore >= 4 && $mScore >= 4 => self::SEGMENT_CHAMPIONS,
            $rScore >= 3 && $fScore >= 3 => self::SEGMENT_LOYAL,
            $rScore >= 3 && $fScore <= 2 && $mScore >= 3 => self::SEGMENT_POTENTIAL,
            $frequency <= 1 => self::SEGMENT_NEW,
            $rScore <= 2 && $fScore >= 3 => self::SEGMENT_AT_RISK,
            $rScore <= 2 && $fScore <= 2 => self::SEGMENT_HIBERNATING,
            default => self::SEGMENT_LOST,
        };
    }

    /**
     * Обновить tier лояльности
     */
    public function updateLoyaltyTier(): string
    {
        $this->loyalty_tier = match(true) {
            $this->total_spent >= 100000 => self::TIER_PLATINUM,
            $this->total_spent >= 50000 => self::TIER_GOLD,
            $this->total_spent >= 20000 => self::TIER_SILVER,
            default => self::TIER_BRONZE,
        };

        return $this->loyalty_tier;
    }

    /**
     * Рассчитать вероятность оттока
     */
    public function calculateChurnProbability(): float
    {
        // Простая эвристика
        $daysSinceLastOrder = $this->last_order_at 
            ? now()->diffInDays($this->last_order_at)
            : 999;

        $probability = 0.0;

        if ($daysSinceLastOrder > 180) {
            $probability += 0.7;
        } elseif ($daysSinceLastOrder > 90) {
            $probability += 0.4;
        } elseif ($daysSinceLastOrder > 60) {
            $probability += 0.2;
        }

        if ($this->total_orders <= 1) {
            $probability += 0.3;
        }

        if ($this->rfm_segment === self::SEGMENT_AT_RISK) {
            $probability += 0.2;
        }

        return min(1.0, $probability);
    }

    /**
     * Получить множитель лояльности
     */
    public function getLoyaltyMultiplier(): float
    {
        return match($this->loyalty_tier) {
            self::TIER_PLATINUM => 3.0,
            self::TIER_GOLD => 2.0,
            self::TIER_SILVER => 1.5,
            default => 1.0,
        };
    }

    /**
     * Получить кэшбэк процент
     */
    public function getCashbackRate(): float
    {
        return match($this->loyalty_tier) {
            self::TIER_PLATINUM => 0.05,
            self::TIER_GOLD => 0.03,
            self::TIER_SILVER => 0.02,
            default => 0.01,
        };
    }

    /**
     * Получить месячный лимит кэшбэка
     */
    public function getCashbackMonthlyLimit(): int
    {
        return match($this->loyalty_tier) {
            self::TIER_PLATINUM => 10000,
            self::TIER_GOLD => 5000,
            self::TIER_SILVER => 3000,
            default => 1000,
        };
    }
}
