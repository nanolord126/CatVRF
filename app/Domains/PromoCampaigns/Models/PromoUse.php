<?php

declare(strict_types=1);

namespace App\Domains\PromoCampaigns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Строгая и бескомпромиссная модель фиксации применения промо-кампании (PromoUse).
 *
 * Категорически отвечает за сохранение каждого факта использования скидки или бонуса конкретным пользователем
 * (user_id) в рамках конкретного заказа (order_id / source_id). Служит финансовым базисом
 * для пересчета израсходованного бюджета кампании (spent_budget).
 */
final class PromoUse extends Model
{
    /**
     * Безусловно отключает автоматическое управление `created_at` и `updated_at`,
     * так как используется только точечный `used_at` (по канону).
     */
    public $timestamps = false;

    /**
     * @var string Безусловное имя таблицы в реляционной базе данных.
     */
    protected $table = 'promo_uses';

    /**
     * @var array<int, string> Категорически защищенный массив разрешенных к заполнению атрибутов.
     */
    protected $fillable = [
        'uuid',
        'correlation_id',
        'promo_campaign_id',
        'tenant_id',
        'user_id',
        'order_id', // или appointment_id / source_id
        'discount_amount',
        'correlation_id',
        'used_at',
    ];

    /**
     * @var array<string, string> Исключительно жесткое приведение типов, чтобы исключить потерю точности копеек.
     */
    protected $casts = [
        'discount_amount' => 'integer',
        'used_at' => 'datetime',
    ];

    /**
     * Возвращает исключительно сильную связь с мастер-кампанией.
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
            if (! $model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
