<?php

declare(strict_types=1);

namespace Modules\PromoCampaign\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Обособленная инфраструктурная Eloquent модель для фиксации каждого отдельного случая использования промокода.
 *
 * Категорически необходима для финансового аудита, сбора аналитики и защиты от злоупотреблений.
 * Строго соответствует требованиям ФЗ-152 и ФЗ-38 о хранении логов применения маркетинговых механик.
 */
final class PromoUseModel extends Model
{
    /**
     * @var string Строго фиксированное наименование таблицы.
     */
    protected $table = 'promo_uses';

    /**
     * @var array<int, string> Ограниченный массив параметров для массового присвоения.
     */
    protected $fillable = [
        'promo_campaign_uuid',
        'tenant_id',
        'user_id',
        'action',
        'discount_amount',
        'correlation_id'
    ];

    /**
     * @var array<string, string> Безусловное приведение базовых типов данных.
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'user_id' => 'integer',
        'discount_amount' => 'integer'
    ];
}
