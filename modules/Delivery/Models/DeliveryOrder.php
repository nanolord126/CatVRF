<?php declare(strict_types=1);

namespace Modules\Delivery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryOrder extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $fillable = [
            'order_number',
            'status',
            'delivery_date',
            'pickup_address',
            'delivery_address',
            'price',
            'distance_km',
        ];
    
        protected $casts = [
            'delivery_date' => 'date',
            'price' => 'decimal:2',
            'distance_km' => 'float',
        ];
}
