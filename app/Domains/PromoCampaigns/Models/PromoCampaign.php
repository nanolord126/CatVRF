<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Models;


use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Scopes\TenantScope;
use App\Domains\PromoCampaigns\Enums\PromoType;
use App\Domains\PromoCampaigns\Enums\PromoStatus;

/**
 * Исключительно фундаментальная модель сущности PromoCampaign (Промо-кампания).
 *
 * Категорически отвечает за персистентное хранение всех маркетинговых акций,
 * жесткий контроль выделенного бюджета (в копейках) и лимитирование использования
 * (как суммарно, так и на одного пользователя).
 * Безусловно использует TenantScope для полной изоляции данных между бизнесами.
 */
final class PromoCampaign extends Model
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}


    /**
     * @var string Строго зафиксированное имя таблицы.
     */
    protected $table = 'promo_campaigns';

    /**
     * @var array<int, string> Категорически полный список полей для безопасного массового заполнения.
     */
    protected $fillable = [
        'uuid',
        'correlation_id',
        'tenant_id',
        'business_group_id',
        'type',
        'code',
        'name',
        'description',
        'start_at',
        'end_at',
        'budget',
        'spent_budget',
        'max_uses_per_user',
        'max_uses_total',
        'min_order_amount',
        'applicable_verticals',
        'applicable_categories',
        'status',
        'correlation_id',
        'created_by',
    ];

    /**
     * @var array<string, string> Абсолютно строгие приведения типов для безопасности данных.
     */
    protected $casts = [
        'type' => PromoType::class,
        'status' => PromoStatus::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'budget' => 'integer',
        'spent_budget' => 'integer',
        'max_uses_per_user' => 'integer',
        'max_uses_total' => 'integer',
        'min_order_amount' => 'integer',
        'applicable_verticals' => 'array',
        'applicable_categories' => 'array',
    ];

    /**
     * Категорически изолирует запросы в пределах текущего тенанта платформы.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Безусловная связь со всеми фактами применений данного промо-кода.
     *
     * @return HasMany
     */
    public function uses(): HasMany
    {
        return $this->hasMany(PromoUse::class, 'promo_campaign_id', 'id');
    }

    /**
     * Абсолютно надежная связь с аудиторскими логами для compliance контроля (ФЗ-38, ФЗ-152).
     *
     * @return HasMany
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(PromoAuditLog::class, 'promo_campaign_id', 'id');
    }
}
