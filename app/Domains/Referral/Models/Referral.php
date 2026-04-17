<?php

declare(strict_types=1);

namespace App\Domains\Referral\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domains\Referral\Enums\ReferralStatus;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Исключительно фундаментальная модель сущности Referral (Реферал).
 *
 * Категорически отвечает за персистентное хранение связи между приглашающим (referrer)
 * и приглашенным (referee), фиксацию платформы миграции и порогов оборота.
 * Абсолютно всегда использует строгий scope для изоляции данных (TenantScope).
 */
final class Referral extends Model
{

    /**
     * @var string $table Жестко заданное имя таблицы в базе данных PostgreSQL.
     */
    protected $table = 'referrals';

    /**
     * @var array<int, string> Категорически полный список полей, доступных для массового заполнения (Mass Assignment).
     */
    protected $fillable = [
        'uuid',
        'correlation_id',
        'referrer_id',
        'referee_id',
        'referral_code',
        'referral_link',
        'status',
        'source_platform',
        'migrated_at',
        'turnover_threshold',
        'spent_threshold',
        'bonus_amount',
        'correlation_id',
        'tenant_id',
    ];

    /**
     * @var array<string, string> Абсолютно строгое приведение типов данных к скалярным значениям.
     */
    protected $casts = [
        'status' => ReferralStatus::class,
        'migrated_at' => 'datetime',
        'turnover_threshold' => 'integer',
        'spent_threshold' => 'integer',
        'bonus_amount' => 'integer', // В копейках
    ];

    /**
     * Безусловная инициализация глобальных Scope для строгой мульти-тенантной изоляции.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Формирует исключительную связь "Один ко Многим" для получения всех начисленных вознаграждений по рефералу.
     *
     * @return HasMany Строго типизированное отношение модели.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class, 'referral_id', 'id');
    }
}
