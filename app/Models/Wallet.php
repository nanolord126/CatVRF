<?php declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Wallet extends Model
{
    protected $table = 'wallets';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'current_balance',
        'hold_amount',
        'cached_balance',
        'correlation_id',
        'uuid',
        'tags',
        'meta',
    ];

    protected $casts = [
        'current_balance' => 'integer',
        'hold_amount' => 'integer',
        'cached_balance' => 'integer',
        'tags' => 'json',
        'meta' => 'json',
    ];

    /**
     * Баланс с учётом hold'ов
     */
    public function getAvailableBalanceAttribute(): int
    {
        return $this->current_balance - $this->hold_amount;
    }

    /**
     * Отношение к транзакциям баланса
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'wallet_id', 'id');
    }

    /**
     * Отношение к платежам
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'wallet_id', 'id');
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('tenant_id', function ($query) {
            if (function_exists('tenant') && tenant('id')) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }
}
