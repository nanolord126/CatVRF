<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiVehicle extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $table = 'taxi_vehicles';

        protected $fillable = [
            'tenant_id',
            'driver_id',
            'fleet_id',
            'brand',
            'model',
            'license_plate',
            'class',
            'status',
            'year',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $casts = [
            'tags' => 'collection',
            'metadata' => 'json',
            'year' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
        }

        public function driver(): BelongsTo
        {
            return $this->belongsTo(TaxiDriver::class, 'driver_id');
        }

        public function fleet(): BelongsTo
        {
            return $this->belongsTo(TaxiFleet::class, 'fleet_id');
        }

        public function rides(): HasMany
        {
            return $this->hasMany(TaxiRide::class, 'vehicle_id');
        }
}
