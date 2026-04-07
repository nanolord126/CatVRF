<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class Vehicle extends Model
{
    use HasFactory;
    use SoftDeletes;

        protected $table = 'auto_vehicles';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'vin',
            'license_plate',
            'brand',
            'model',
            'year',
            'color',
            'type',
            'status',
            'technical_specs',
            'amenities',
            'price_kopecks',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'technical_specs' => 'json',
            'amenities' => 'json',
            'tags' => 'json',
            'price_kopecks' => 'integer',
            'year' => 'integer',
        ];

        /**
         * КАНОН 2026: Global Scope + ID Generation.
         */
        protected static function booted(): void
        {
            static::creating(function (Vehicle $vehicle) {
                $vehicle->uuid = $vehicle->uuid ?? (string) Str::uuid();
                $vehicle->tenant_id = $vehicle->tenant_id ?? (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('auto_vehicles.tenant_id', tenant()->id);
                }
            });
        }

        /**
         * Связанные заказы на ремонт.
         */
        public function repairOrders(): HasMany
        {
            return $this->hasMany(AutoRepairOrder::class, 'vehicle_id');
        }

        /**
         * История поездок такси.
         */
        public function taxiRides(): HasMany
        {
            return $this->hasMany(TaxiRide::class, 'vehicle_id');
        }

        /**
         * Бронирования мойки.
         */
        public function washBookings(): HasMany
        {
            return $this->hasMany(WashBooking::class, 'vehicle_id');
        }

        /**
         * Форматированное название (Brand + Model + Plate).
         */
        public function getDisplayNameAttribute(): string
        {
            return "{$this->brand} {$this->model} (" . ($this->license_plate ?? 'No Plate') . ")";
        }
}
