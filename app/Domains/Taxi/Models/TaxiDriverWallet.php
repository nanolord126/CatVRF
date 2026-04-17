<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class TaxiDriverWallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taxi_driver_wallets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'driver_id',
        'balance_kopeki',
        'frozen_kopeki',
        'total_earned_kopeki',
        'total_withdrawn_kopeki',
        'currency',
        'status',
        'is_verified',
        'verification_document_id',
        'last_withdrawal_at',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'balance_kopeki' => 'integer',
        'frozen_kopeki' => 'integer',
        'total_earned_kopeki' => 'integer',
        'total_withdrawn_kopeki' => 'integer',
        'is_verified' => 'boolean',
        'last_withdrawal_at' => 'datetime',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Статусы кошелька.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_FROZEN = 'frozen';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_CLOSED = 'closed';

    protected static function booted(): void
    {
        static::creating(function (TaxiDriverWallet $wallet) {
            $wallet->uuid = $wallet->uuid ?? (string) Str::uuid();
            $wallet->tenant_id = $wallet->tenant_id ?? (tenant()->id ?? 1);
            $wallet->status = $wallet->status ?? self::STATUS_ACTIVE;
            $wallet->balance_kopeki = $wallet->balance_kopeki ?? 0;
            $wallet->frozen_kopeki = $wallet->frozen_kopeki ?? 0;
            $wallet->total_earned_kopeki = $wallet->total_earned_kopeki ?? 0;
            $wallet->total_withdrawn_kopeki = $wallet->total_withdrawn_kopeki ?? 0;
            $wallet->correlation_id = $wallet->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Отношения.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TaxiTransaction::class, 'driver_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(TaxiWithdrawal::class, 'wallet_id');
    }

    /**
     * Получить баланс в рублях.
     */
    public function getBalanceInRubles(): float
    {
        return $this->balance_kopeki / 100;
    }

    /**
     * Получить замороженные средства в рублях.
     */
    public function getFrozenInRubles(): float
    {
        return $this->frozen_kopeki / 100;
    }

    /**
     * Получить доступный баланс (без замороженных).
     */
    public function getAvailableBalanceKopeki(): int
    {
        return $this->balance_kopeki - $this->frozen_kopeki;
    }

    /**
     * Получить доступный баланс в рублях.
     */
    public function getAvailableBalanceInRubles(): float
    {
        return $this->getAvailableBalanceKopeki() / 100;
    }

    /**
     * Получить общий заработок в рублях.
     */
    public function getTotalEarnedInRubles(): float
    {
        return $this->total_earned_kopeki / 100;
    }

    /**
     * Проверить, активен ли кошелек.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->is_verified;
    }

    /**
     * Проверить, заблокирован ли кошелек.
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED || $this->status === self::STATUS_FROZEN;
    }

    /**
     * Пополнить кошелек.
     */
    public function credit(int $amountKopeki, string $reason = ''): void
    {
        $this->increment('balance_kopeki', $amountKopeki);
        $this->increment('total_earned_kopeki', $amountKopeki);
    }

    /**
     * Списать с кошелька.
     */
    public function debit(int $amountKopeki, string $reason = ''): void
    {
        if ($this->balance_kopeki < $amountKopeki) {
            throw new \InvalidArgumentException('Insufficient balance');
        }

        $this->decrement('balance_kopeki', $amountKopeki);
        $this->increment('total_withdrawn_kopeki', $amountKopeki);
    }

    /**
     * Заморозить средства.
     */
    public function freeze(int $amountKopeki): void
    {
        if ($this->balance_kopeki < $amountKopeki + $this->frozen_kopeki) {
            throw new \InvalidArgumentException('Insufficient balance to freeze');
        }

        $this->increment('frozen_kopeki', $amountKopeki);
    }

    /**
     * Разморозить средства.
     */
    public function unfreeze(int $amountKopeki): void
    {
        if ($this->frozen_kopeki < $amountKopeki) {
            throw new \InvalidArgumentException('Insufficient frozen amount');
        }

        $this->decrement('frozen_kopeki', $amountKopeki);
    }

    /**
     * Пометить как активный.
     */
    public function markAsActive(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Пометить как замороженный.
     */
    public function markAsFrozen(): void
    {
        $this->update(['status' => self::STATUS_FROZEN]);
    }

    /**
     * Пометить как заблокированный.
     */
    public function markAsBlocked(): void
    {
        $this->update(['status' => self::STATUS_BLOCKED]);
    }
}
