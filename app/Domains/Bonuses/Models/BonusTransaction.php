<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use App\Traits\TenantScoped;
use App\Domains\Bonuses\Enums\BonusStatus;
use App\Domains\Bonuses\Enums\BonusType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель транзакции бонусов.
 *
 * Записывает все операции с бонусами: начисление, трату, вывод, экспирацию.
 * Связана с Wallet через wallet_id для интеграции с кошельком.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property int $wallet_id
 * @property int|null $bonus_rule_id
 * @property int|null $bonus_campaign_id
 * @property int $user_id
 * @property BonusType $type
 * @property BonusStatus $status
 * @property int $amount Сумма в копейках (положительная для начисления, отрицательная для списания)
 * @property string|null $source_type Тип источника (order, referral, promo_code и т.д.)
 * @property int|null $source_id ID источника
 * @property string|null $correlation_id
 * @property array|null $metadata Дополнительные данные
 * @property array|null $tags Теги для фильтрации
 * @property \Carbon\Carbon|null $credited_at Когда бонус был зачислен (разблокирован)
 * @property \Carbon\Carbon|null $hold_until До когда бонус на hold
 * @property \Carbon\Carbon|null $expires_at Когда бонус истекает
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class BonusTransaction extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'bonus_transactions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'wallet_id',
        'bonus_rule_id',
        'bonus_campaign_id',
        'user_id',
        'type',
        'status',
        'amount',
        'source_type',
        'source_id',
        'correlation_id',
        'metadata',
        'tags',
        'credited_at',
        'hold_until',
        'expires_at',
    ];

    protected $casts = [
        'type' => BonusType::class,
        'status' => BonusStatus::class,
        'amount' => 'integer',
        'metadata' => 'json',
        'tags' => 'json',
        'credited_at' => 'datetime',
        'hold_until' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /** @return BelongsTo<\App\Domains\Wallet\Models\Wallet, self> */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Wallet\Models\Wallet::class, 'wallet_id');
    }

    /** @return BelongsTo<BonusRule, self> */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(BonusRule::class, 'bonus_rule_id');
    }

    /** @return BelongsTo<BonusCampaign, self> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(BonusCampaign::class, 'bonus_campaign_id');
    }

    /**
     * Scope: только активные бонусы (доступны для использования).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', BonusStatus::CREDITED)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope: бонусы на hold (ожидание разблокировки).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', BonusStatus::PENDING);
    }

    /**
     * Scope: просроченные hold (готовы к разблокировке).
     */
    public function scopeReadyToUnlock(Builder $query): Builder
    {
        return $query->where('status', BonusStatus::PENDING)
            ->where('hold_until', '<=', now());
    }

    /**
     * Scope: истёкшие бонусы.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', BonusStatus::CREDITED)
            ->where('expires_at', '<', now());
    }

    /**
     * Scope: транзакции конкретного пользователя.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: транзакции конкретного типа.
     */
    public function scopeForType(Builder $query, BonusType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: транзакции по источнику.
     */
    public function scopeForSource(Builder $query, string $sourceType, ?int $sourceId = null): Builder
    {
        $query = $query->where('source_type', $sourceType);
        
        if ($sourceId !== null) {
            $query->where('source_id', $sourceId);
        }
        
        return $query;
    }

    /**
     * Проверить, истёк ли бонус.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Проверить, готов ли бонус к разблокировке.
     */
    public function isReadyToUnlock(): bool
    {
        return $this->status === BonusStatus::PENDING
            && $this->hold_until !== null
            && $this->hold_until->isPast();
    }

    /**
     * Проверить, доступен ли бонус для использования.
     */
    public function isAvailable(): bool
    {
        return $this->status === BonusStatus::CREDITED
            && !$this->isExpired();
    }

    /**
     * Перевести бонус в указанный статус.
     */
    public function transitionTo(BonusStatus $newStatus, ?string $correlationId = null): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;
        $this->correlation_id = $correlationId ?? $this->correlation_id;

        if ($newStatus === BonusStatus::CREDITED) {
            $this->credited_at = now();
        }

        return $this->save();
    }

    /**
     * Получить доступный баланс бонусов пользователя.
     *
     * @param  int  $userId
     * @param  int  $tenantId
     * @return int Сумма в копейках
     */
    public static function getAvailableBalance(int $userId, int $tenantId): int
    {
        return self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->active()
            ->sum('amount');
    }

    /**
     * Получить сумму бонусов на hold.
     *
     * @param  int  $userId
     * @param  int  $tenantId
     * @return int Сумма в копейках
     */
    public static function getPendingBalance(int $userId, int $tenantId): int
    {
        return self::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->pending()
            ->sum('amount');
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', static function (Builder $builder): void {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
