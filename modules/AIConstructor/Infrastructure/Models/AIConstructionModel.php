<?php

declare(strict_types=1);

namespace Modules\AIConstructor\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Исключительно инфраструктурная ORM-базированная Eloquent модель для категорического хранения логов
 * успешных AI-генераций (проектов, капсул, дизайнов), инициированных пользователями.
 *
 * Безупречно описывает структуру таблицы в Postgres, гарантируя теннант-изоляцию и строгость JSON-кастов.
 */
final class AIConstructionModel extends Model
{
    /**
     * @var string Явное и абсолютно точное определение системного имени таблицы в базе данных.
     */
    protected $table = 'user_ai_designs';

    /**
     * @var array<int, string> Исчерпывающий список атрибутов, категорически доступных для присвоения.
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'tenant_id',
        'vertical',
        'type',
        'design_data',
        'suggestion_item_ids',
        'confidence_score',
        'correlation_id'
    ];

    /**
     * @var array<string, string> Строго типизированные преобразования (casts) полей базы данных в примитивы PHP.
     */
    protected $casts = [
        'user_id' => 'integer',
        'tenant_id' => 'integer',
        'design_data' => 'json',
        'suggestion_item_ids' => 'json',
        'confidence_score' => 'float',
    ];
}
