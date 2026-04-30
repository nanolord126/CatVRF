<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LoyaltyProfile — профиль лояльности клиента.
 * 
 * Хранит информацию о программе лояльности, уровне клиента,
 * накопленных баллах и истории транзакций.
 */
final class LoyaltyProfile extends Model
{
    use TenantScoped;

    protected $table = 'beauty_loyalty_profiles';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'loyalty_program_id',
        'tier', // bronze, silver, gold, platinum
        'points_balance',
        'total_points_earned',
        'total_points_redeemed',
        'lifetime_value',
        'membership_start_date',
        'last_activity_at',
    ];

    protected $casts = [
        'membership_start_date' => 'date',
        'last_activity_at' => 'datetime',
        'points_balance' => 'integer',
        'total_points_earned' => 'integer',
        'total_points_redeemed' => 'integer',
        'lifetime_value' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Начислить баллы
     */
    public function addPoints(int $points): void
    {
        $this->increment('points_balance', $points);
        $this->increment('total_points_earned', $points);
        $this->update(['last_activity_at' => now()]);
        $this->checkTierUpgrade();
    }

    /**
     * Списать баллы
     */
    public function redeemPoints(int $points): bool
    {
        if ($this->points_balance < $points) {
            return false;
        }

        $this->decrement('points_balance', $points);
        $this->increment('total_points_redeemed', $points);
        $this->update(['last_activity_at' => now()]);
        return true;
    }

    /**
     * Проверить возможность повышения уровня
     */
    protected function checkTierUpgrade(): void
    {
        $newTier = $this->calculateTier();
        if ($newTier !== $this->tier) {
            $this->update(['tier' => $newTier]);
        }
    }

    /**
     * Рассчитать уровень на основе накопленных баллов
     */
    protected function calculateTier(): string
    {
        $totalPoints = $this->total_points_earned;

        return match (true) {
            $totalPoints >= 10000 => 'platinum',
            $totalPoints >= 5000 => 'gold',
            $totalPoints >= 2000 => 'silver',
            default => 'bronze',
        };
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
