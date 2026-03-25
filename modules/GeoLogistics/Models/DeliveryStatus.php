declare(strict_types=1);

<?php

declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Model;

final /**
 * DeliveryStatus
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class DeliveryStatus extends Model
{
    public $timestamps = false;

    protected $table = 'delivery_statuses';

    protected $fillable = [
        'delivery_id',
        'status',
        'latitude',
        'longitude',
        'timestamp',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'timestamp' => 'datetime',
    ];
}
