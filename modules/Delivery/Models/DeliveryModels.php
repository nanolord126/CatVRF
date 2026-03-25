<?php

namespace Modules\Deliver

/**
 * DeliveryZone
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new DeliveryZone();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace Modules\Delivery\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
y\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Common\HasEcosystemFeatures;

class DeliveryZone extends Model
{
    use HasEcosystemFeatures;

    protected $fillable = ['name', 'radius_km', 'delivery_fee', 'is_active', 'geo_json'];

    protected $casts = [
        'is_active' => 'boolean',
        'geo_json' => 'array',
        'radius_km' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
    ];
}

class DeliveryOrder extends Model
{
    use HasEcosystemFeatures;

    protected $fillable = [
        'customer_id', 'delivery_zone_id', 'status', 'address', 
        'latitude', 'longitude', 'delivery_fee', 'correlation_id'
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function zone()
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
