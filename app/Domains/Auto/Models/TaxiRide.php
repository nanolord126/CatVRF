<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiRide extends Model
{

    protected $table = 'auto_taxi_rides';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'vehicle_id',
            'driver_id',
            'passenger_id',
            'pickup_point',
            'dropoff_point',
            'pickup_address',
            'dropoff_address',
            'status',
            'price_kopecks',
            'surge_multiplier',
            'started_at',
            'finished_at',
            'correlation_id',
        ];

        protected $casts = [
            'price_kopecks' => 'integer',
            'surge_multiplier' => 'float',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'pickup_point' => 'string', // PostGIS Point handling required if raw
            'dropoff_point' => 'string',
        ];

        /**
         * КАНОН 2026: Automatic ID & Tenant Scoping.
         */
        protected static function booted(): void
        {
            static::creating(function (TaxiRide $ride) {
                $ride->uuid = $ride->uuid ?? (string) Str::uuid();
                $ride->tenant_id = $ride->tenant_id ?? (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('auto_taxi_rides.tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Связь с ТС.
         */
        public function vehicle(): BelongsTo
        {
            return $this->belongsTo(Vehicle::class, 'vehicle_id');
        }

        /**
         * Расчет базового дохода водителя.
         */
        public function getEstimatedDriverEarnings(): int
        {
            return (int) ($this->price_kopecks * 0.85); // Пример комиссии 15%
        }
}
