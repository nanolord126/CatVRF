<?php declare(strict_types=1);

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryZone extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
