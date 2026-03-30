<?php declare(strict_types=1);

namespace Modules\GeoLogistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DeliveryStatus extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
