<?php

declare(strict_types=1);

namespace App\Domains\Bonuses\Models;

use App\Traits\TenantScoped;
use App\Domains\Bonuses\Enums\BonusType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель бонусной кампании.
 *
 * Определяет временные акции и специальные предложения с бонусами.
 * Может быть привязана к конкретным вертикалям или глобальной.
 *
 * @property int $id
 * @property string $uuid
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $name
 * @property string|null $description
 * @property BonusType $type
 * @property float $multiplier Множитель для кампании
 * @property int $fixed_amount Фиксированная сумма в копейках
 * @property float $percentage Процент от суммы
 * @property int $max_awards_per_user Максимум наград на пользователя
 * @property int $max_total_awards Общий максимум наград
 * @property int $awards_count Текущее количество наград
 * @property string|null $promo_code Промокод (если применимо)
 * @property string|null $vertical_code Код вертикали (null = глобальная)
 * @property array|null $target_segments Целевые сегменты
 * @property bool $is_active
 * @property bool $is_public Видна ли в UI
 * @property \Carbon\Carbon $starts_at
 * @property \Carbon\Carbon $ends_at
 * @property array|null $metadata Дополнительные параметры
 * @property array|null $tags Теги для фильтрации
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
final class BonusCampaign extends Model
{
    use TenantScoped;
    use SoftDeletes;

    protected $table = 'bonus_campaigns';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'name',
        'description',
        'type',
        'multiplier',
        'fixed_amount',
        'percentage',
        'max_awards_per_user',
        'max_total_awards',
        'awards_count',
        'promo_code',
        'vertical_code',
        'target_segments',
        'is_active',
        'is_public',
        'starts_at',
        'ends_at',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'type' => BonusType::class,
        'multiplier' => 'float',
        'fixed_amount' => 'integer',
        'percentage' => 'float',
        'max_awards_per_user' => 'integer',
        'max_total_awards' => 'integer',
        'awards_count' => 'integer',
        'target_segments' => 'json',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    /** @return HasMany<BonusTransaction> Транзакции по этой кампании. */
    public function transactions(): HasMany
    {
        return $this->hasMany(BonusTransaction::class, 'bonus_campaign_id');
    }

    /**
     * Scope: только активные кампании.
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
     * Scope: публичные кампании (видимые в UI).
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope: кампании для конкретной вертикали.
     */
    public function scopeForVertical(Builder $query, ?string $verticalCode): Builder
    {
        return $query->where(function (Builder $q) use ($verticalCode) {
            $q->whereNull('vertical_code')
                ->orWhere('vertical_code', $verticalCode);
        });
    }

    /**
     * Scope: кампании по промокоду.
     */
    public function scopeForPromoCode(Builder $query, string $promoCode): Builder
    {
        return $query->where('promo_code', $promoCode);
    }

    /**
     * Scope: кампании с доступными наградами.
     */
    public function scopeWithAvailableAwards(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('max_total_awards')
                ->orWhereColumn('awards_count', '<', 'max_total_awards');
        });
    }

    /**
     * Проверить, активна ли кампания.
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_total_awards && $this->awards_count >= $this->max_total_awards) {
            return false;
        }

        return true;
    }

    /**
     * Проверить, доступна ли кампания для пользователя.
     */
    public function isAvailableForUser(int $userId, ?string $userSegment = null): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->max_awards_per_user) {
            $userAwards = $this->transactions()
                ->where('user_id', $userId)
                ->count();

            if ($userAwards >= $this->max_awards_per_user) {
                return false;
            }
        }

        if ($userSegment && $this->target_segments) {
            if (!in_array($userSegment, $this->target_segments, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Рассчитать сумму бонуса для кампании.
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
            return (int) ($baseAmount * ($this->percentage / 100) * $this->multiplier);
        }

        return 0;
    }

    /**
     * Увеличить счётчик наград.
     */
    public function incrementAwardsCount(int $increment = 1): bool
    {
        return $this->increment('awards_count', $increment) > 0;
    }

    /**
     * Получить оставшееся количество наград.
     */
    public function getRemainingAwards(): ?int
    {
        if ($this->max_total_awards === null) {
            return null;
        }

        return max(0, $this->max_total_awards - $this->awards_count);
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
