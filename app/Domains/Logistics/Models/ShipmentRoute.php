<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ShipmentRoute extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory, SoftDeletes;

        protected $table = 'shipment_routes';

        protected $fillable = [
            'tenant_id',
            'courier_service_id',
            'waypoints',
            'total_distance',
            'estimated_time_minutes',
            'is_optimized',
            'correlation_id',
        ];

        protected $casts = [
            'waypoints' => 'collection',
            'total_distance' => 'float',
            'is_optimized' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($query) {
                if (auth()->check()) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }

        public function courierService(): BelongsTo
        {
            return $this->belongsTo(CourierService::class);
        }
}
