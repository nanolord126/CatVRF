<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\BalanceTransaction;
use App\Models\BusinessGroup;
use App\Models\Tenant;
use Carbon\Carbon;

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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class Wallet extends Model
{
    use TenantScoped;

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

    /** @return BelongsTo<Tenant, self> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /** @return BelongsTo<BusinessGroup, self> */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    /** @return HasMany<Model> Все балансовые транзакции этого кошелька. */
    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class, 'wallet_id');
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

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::addGlobalScope('businessGroup', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->business_group_id) {
                $builder->where('business_group_id', tenant()?->business_group_id);
            }
        });

        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
