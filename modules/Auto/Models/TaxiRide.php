<?php declare(strict_types=1);

namespace Modules\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiRide extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;
    
        protected $table = 'taxi_rides';
    
        protected $fillable = [
            'tenant_id',
            'driver_id',
            'passenger_id',
            'vehicle_id',
            'vehicle_class',
            'pickup_lat',
            'pickup_lng',
            'dropoff_lat',
            'dropoff_lng',
            'distance_km',
            'fare_amount',
            'surge_multiplier',
            'status',
            'completed_at',
            'cancelled_at',
            'cancellation_reason',
            'correlation_id',
            'tags',
            'uuid',
        ];
    
        protected $casts = [
            'fare_amount'      => 'integer',
            'distance_km'      => 'float',
            'surge_multiplier' => 'float',
            'tags'             => 'json',
            'completed_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    
        protected static function newFactory(): \Modules\Auto\Database\Factories\TaxiRideFactory
        {
            return \Modules\Auto\Database\Factories\TaxiRideFactory::new();
        }
}
