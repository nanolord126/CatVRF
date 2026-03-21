<?php

declare(strict_types=1);

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Модель кошелька для tenant / business_group / user.
 * Согласно КАНОН 2026: current_balance вычисляется из balance_transactions, held_amount отслеживается отдельно.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int|null $user_id
 * @property int|null $owner_id Morphable ID (если используется polymorphic)
 * @property string|null $owner_type Morphable type (если используется polymorphic)
 * @property int $current_balance Текущий баланс в копейках (вычисляется)
 * @property int $held_amount Зарезервированные средства в копейках
 * @property string $currency Валюта (по умолчанию RUB)
 * @property string|null $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class Wallet extends Model
{
    use SoftDeletes;

    protected $table = 'wallets';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id',
        'owner_id',
        'owner_type',
        'current_balance',
        'held_amount',
        'currency',
        'uuid',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'current_balance' => 'integer',
        'held_amount' => 'integer',
        'tags' => 'json',
    ];

    protected $hidden = [];

    /**
     * Получить владельца кошелька (morphable relation).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Получить все транзакции кошелька.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(\Modules\Wallet\Models\WalletTransaction::class, 'wallet_id');
    }

    /**
     * Получить доступный баланс (текущий - зарезервированный).
     */
    public function getAvailableBalance(): int
    {
        return $this->current_balance - $this->held_amount;
    }

    /**
     * Получить процент использования баланса.
     */
    public function getUsagePercentage(): float
    {
        if ($this->current_balance <= 0) {
            return 0.0;
        }

        return ($this->held_amount / $this->current_balance) * 100.0;
    }
}
