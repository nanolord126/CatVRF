<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Исключительно инфраструктурная Eloquent ORM модель для категорического хранения промо-кампаний.
 *
 * Строго соответствует архитектурным канонам базы данных, обладая обязательными аттрибутами:
 * uuid для идентификации, tenant_id для изоляции данных, correlation_id для надежного аудита мутаций.
 */
final class PromoCampaignModel extends Model
{
    /**
     * @var string Явное и абсолютно точное определение имени таблицы в базе данных.
     */
    protected $table = 'promo_campaigns';

    /**
     * @var array<int, string> Исчерпывающий список атрибутов, строго доступных для массового заполнения (mass assignment).
     */
    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'type',
        'code',
        'status',
        'budget',
        'spent_budget',
        'min_order_amount',
        'max_uses_total',
        'current_uses',
        'start_at',
        'end_at',
        'correlation_id',
        'tags'
    ];

    /**
     * @var array<string, string> Строго типизированные преобразования (casts) полей базы данных в примитивы PHP.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'budget' => 'integer',
        'spent_budget' => 'integer',
        'min_order_amount' => 'integer',
        'max_uses_total' => 'integer',
        'current_uses' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'tags' => 'json'
    ];
}
