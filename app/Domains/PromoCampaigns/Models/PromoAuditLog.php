<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Исключительная модель строгого аудита операций с маркетинговыми кампаниями (PromoAuditLog).
 *
 * Категорически необходима для соответствия законодательным нормам (ФЗ-38 "О рекламе" и ФЗ-152),
 * обеспечивая бескомпромиссное хронологическое логирование всех фактов создания, применения,
 * отмены и исчерпания бюджетов акций сроком хранения минимум 3 года.
 */
final class PromoAuditLog extends Model
{
    /**
     * Безжалостно отключаем стандартные timestamp колонки (используем только created_at).
     */
    public const UPDATED_AT = null;

    /**
     * @var string Жестко определенное имя таблицы.
     */
    protected $table = 'promo_audit_logs';

    /**
     * @var array<int, string> Построгий список защищенных полей.
     */
    protected $fillable = [
        'uuid',
        'correlation_id',
        'promo_campaign_id',
        'action', // created, applied, cancelled, budget_exhausted
        'user_id', // оператор или клиент
        'details', // jsonb
        'correlation_id',
        'created_at',
    ];

    /**
     * @var array<string, string> Безусловное приведение JSONB нагрузки к массиву.
     */
    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Связь с инспектируемой кампанией.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromoCampaign::class, 'promo_campaign_id', 'id');
    }


    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
