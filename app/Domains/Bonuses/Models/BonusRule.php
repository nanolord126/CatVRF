<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use App\Traits\TenantScoped;
use App\Domains\Bonuses\Enums\BonusType;
use App\Domains\Bonuses\Enums\BonusTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель правила начисления бонусов.
 *
 * Определяет условия и множители для разных типов бонусов.
 * Хранится в БД для динамического управления через Filament.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property BonusType $type
 * @property float $multiplier Множитель для начисления (1.0 = 100%)
 * @property int $fixed_amount Фиксированная сумма в копейках (если применимо)
 * @property float $percentage Процент от суммы (если применимо)
 * @property int $min_amount Минимальная сумма для начисления
 * @property int $max_amount Максимальная сумма для начисления
 * @property int $hold_period_days Период hold в днях
 * @property int $expiry_days Срок действия в днях
 * @property bool $is_active
 * @property bool $is_ab_test Участвует в A/B тесте
 * @property string|null $ab_test_variant Вариант A/B теста
 * @property BonusTier|null $min_tier Минимальный tier для применения
 * @property string|null $vertical_code Код вертикали (null = для всех)
 * @property array|null $metadata Дополнительные параметры
 * @property array|null $tags Теги для фильтрации
 * @property \Carbon\Carbon $starts_at
 * @property \Carbon\Carbon $ends_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class BonusRule extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'bonus_rules';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'type',
        'multiplier',
        'fixed_amount',
        'percentage',
        'min_amount',
        'max_amount',
        'hold_period_days',
        'expiry_days',
        'is_active',
        'is_ab_test',
        'ab_test_variant',
        'min_tier',
        'vertical_code',
        'metadata',
        'tags',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'type' => BonusType::class,
        'multiplier' => 'float',
        'fixed_amount' => 'integer',
        'percentage' => 'float',
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'hold_period_days' => 'integer',
        'expiry_days' => 'integer',
        'is_active' => 'boolean',
        'is_ab_test' => 'boolean',
        'min_tier' => BonusTier::class,
        'metadata' => 'json',
        'tags' => 'json',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /** @return HasMany<BonusTransaction> Транзакции по этому правилу. */
    public function transactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class, 'bonus_rule_id');
    }

    /**
     * Scope: только активные правила.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope: правила для конкретного типа бонуса.
     */
    public function scopeForType(Builder $query, BonusType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: правила для конкретной вертикали.
     */
    public function scopeForVertical(Builder $query, ?string $verticalCode): Builder
    {
        return $query->where(function (Builder $q) use ($verticalCode) {
            $q->whereNull('vertical_code')
                ->orWhere('vertical_code', $verticalCode);
        });
    }

    /**
     * Scope: правила для конкретного tier.
     */
    public function scopeForTier(Builder $query, BonusTier $tier): Builder
    {
        return $query->where(function (Builder $q) use ($tier) {
            $q->whereNull('min_tier')
                ->orWhere('min_tier', '<=', $tier);
        });
    }

    /**
     * Scope: A/B тестовые правила.
     */
    public function scopeAbTest(Builder $query, ?string $variant = null): Builder
    {
        $query = $query->where('is_ab_test', true);
        
        if ($variant !== null) {
            $query->where('ab_test_variant', $variant);
        }
        
        return $query;
    }

    /**
     * Рассчитать сумму бонуса на основе правил.
     *
     * @param  int  $baseAmount  Базовая сумма в копейках
     * @return int Сумма бонуса в копейках
     */
    public function calculateBonusAmount(int $baseAmount): int
    {
        if ($this->fixed_amount > 0) {
            return (int) ($this->fixed_amount * $this->multiplier);
        }

        if ($this->percentage > 0) {
            $calculated = (int) ($baseAmount * ($this->percentage / 100) * $this->multiplier);
            
            if ($this->min_amount > 0 && $calculated < $this->min_amount) {
                return $this->min_amount;
            }
            
            if ($this->max_amount > 0 && $calculated > $this->max_amount) {
                return $this->max_amount;
            }
            
            return $calculated;
        }

        return 0;
    }

    /**
     * Проверить, применимо ли правило к указанным условиям.
     */
    public function isApplicable(
        int $baseAmount,
        BonusTier $userTier,
        ?string $verticalCode = null,
    ): bool {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->min_tier && $userTier->value < $this->min_tier->value) {
            return false;
        }

        if ($this->vertical_code && $this->vertical_code !== $verticalCode) {
            return false;
        }

        if ($this->min_amount > 0 && $baseAmount < $this->min_amount) {
            return false;
        }

        return true;
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
