<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Модель кошелька тенанта или бизнес-группы.
 *
 * Категорически запрещено прямым SQL-запросом менять current_balance и hold_amount.
 * Все мутации баланса — только через WalletService (credit/debit/hold/releaseHold).
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int $current_balance Баланс в копейках
 * @property int $hold_amount Замороженная сумма в копейках
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property array|null $metadata
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class Wallet extends Model
{
    protected $table = 'wallets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'current_balance',
        'hold_amount',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'current_balance' => 'integer',
        'hold_amount' => 'integer',
        'tags' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        static::addGlobalScope('businessGroup', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->business_group_id) {
                $builder->where('business_group_id', tenant()?->business_group_id);
            }
        });

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    /** @return BelongsTo<\App\Models\Tenant, self> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<\App\Models\BusinessGroup, self> */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    /** @return HasMany<Model> Все балансовые транзакции этого кошелька. */
    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(\App\Models\BalanceTransaction::class, 'wallet_id');
    }

    /** Доступный баланс = текущий − замороженный. */
    public function getAvailableBalanceAttribute(): int
    {
        return $this->current_balance - $this->hold_amount;
    }

    /** Scope: фильтрация по бизнес-группе (B2B-изоляция). */
    public function scopeForBusinessGroup(Builder $query, int $businessGroupId): Builder
    {
        return $query->where('business_group_id', $businessGroupId);
    }

    /** Scope: только активные кошельки. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
