declare(strict_types=1);

<?php

declare(strict_types=1);

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * DeliveryZone
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_km',
        'delivery_fee',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_km' => 'float',
        'delivery_fee' => 'decimal:2',
    ];
}
