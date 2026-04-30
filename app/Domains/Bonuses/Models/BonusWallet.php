<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BonusWallet - Bonus wallet per user
 * 
 * Tracks bonus balance separately from main wallet.
 * Provides clear separation of bonus funds from real money.
 * 
 * @property string $id
 * @property string $uuid
 * @property string|null $tenant_id
 * @property string|null $business_group_id
 * @property string $user_id
 * @property string|null $main_wallet_id
 * @property int $available_balance
 * @property int $pending_balance
 * @property int $total_earned
 * @property int $total_spent
 * @property int $total_withdrawn
 * @property string $user_type
 * @property string|null $tier
 * @property bool $can_withdraw
 * @property int $max_balance
 * @property int $max_withdrawal_percentage
 * @property int $transaction_count
 * @property \Illuminate\Support\Carbon|null $last_transaction_at
 * @property array|null $metadata
 * @property string|null $correlation_id
 * @property array|null $tags
 */
final class BonusWallet extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'bonus_wallets';

    protected $fillable = [
        'id',
        'uuid',
        'tenant_id',
        'business_group_id',
        'user_id',
        'main_wallet_id',
        'available_balance',
        'pending_balance',
        'total_earned',
        'total_spent',
        'total_withdrawn',
        'user_type',
        'tier',
        'can_withdraw',
        'max_balance',
        'max_withdrawal_percentage',
        'transaction_count',
        'last_transaction_at',
        'metadata',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'available_balance' => 'integer',
        'pending_balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'total_withdrawn' => 'integer',
        'can_withdraw' => 'boolean',
        'max_balance' => 'integer',
        'max_withdrawal_percentage' => 'integer',
        'transaction_count' => 'integer',
        'last_transaction_at' => 'datetime',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant('id')) {
                $builder->where('tenant_id', tenant('id'));
            }
        });

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    // Relationships

    public function transactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class, 'wallet_id');
    }

    // Scopes

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeB2B(Builder $query): Builder
    {
        return $query->where('user_type', 'b2b');
    }

    public function scopeB2C(Builder $query): Builder
    {
        return $query->where('user_type', 'b2c');
    }

    public function scopeCanWithdraw(Builder $query): Builder
    {
        return $query->where('can_withdraw', true);
    }

    // Domain Methods

    public function getTotalBalance(): int
    {
        return $this->available_balance + $this->pending_balance;
    }

    public function hasSufficientBalance(int $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    public function canWithdrawAmount(int $amount): bool
    {
        if (!$this->can_withdraw) {
            return false;
        }

        $maxWithdraw = (int) ($this->available_balance * $this->max_withdrawal_percentage / 100);
        
        return $amount <= $maxWithdraw;
    }

    public function isBelowMaxBalance(int $additionalAmount): bool
    {
        return ($this->available_balance + $additionalAmount) <= $this->max_balance;
    }

    public function creditAvailable(int $amount): void
    {
        $this->available_balance += $amount;
        $this->total_earned += $amount;
        $this->transaction_count++;
        $this->last_transaction_at = now();
        $this->save();
    }

    public function creditPending(int $amount): void
    {
        $this->pending_balance += $amount;
        $this->total_earned += $amount;
        $this->transaction_count++;
        $this->last_transaction_at = now();
        $this->save();
    }

    public function debitAvailable(int $amount): void
    {
        $this->available_balance -= $amount;
        $this->total_spent += $amount;
        $this->transaction_count++;
        $this->last_transaction_at = now();
        $this->save();
    }

    public function moveFromPendingToAvailable(int $amount): void
    {
        $this->pending_balance -= $amount;
        $this->available_balance += $amount;
        $this->save();
    }

    public function withdraw(int $amount): void
    {
        $this->available_balance -= $amount;
        $this->total_withdrawn += $amount;
        $this->transaction_count++;
        $this->last_transaction_at = now();
        $this->save();
    }

    public function expire(int $amount): void
    {
        $this->available_balance -= $amount;
        $this->save();
    }

    public function isB2B(): bool
    {
        return $this->user_type === 'b2b';
    }

    public function isB2C(): bool
    {
        return $this->user_type === 'b2c';
    }

    public function isGoldOrPlatinum(): bool
    {
        return in_array($this->tier, ['gold', 'platinum'], true);
    }

    public static function getOrCreateForUser(string $userId, string $userType = 'b2c', ?string $tier = null): self
    {
        $wallet = self::forUser($userId)->first();

        if ($wallet) {
            return $wallet;
        }

        $canWithdraw = $userType === 'b2b' && in_array($tier, ['gold', 'platinum'], true);

        return self::create([
            'user_id' => $userId,
            'tenant_id' => tenant('id'),
            'user_type' => $userType,
            'tier' => $tier,
            'can_withdraw' => $canWithdraw,
            'available_balance' => 0,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_spent' => 0,
            'total_withdrawn' => 0,
            'max_balance' => config('bonuses.max_bonus_balance', 1_000_000),
            'max_withdrawal_percentage' => 100,
            'transaction_count' => 0,
        ]);
    }
}
