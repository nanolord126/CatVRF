<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Поездка такси.
 * Production 2026.
 */
final class TaxiRide extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $table = 'taxi_rides';

    protected $fillable = [
        'tenant_id',
        'passenger_id',
        'driver_id',
        'vehicle_id',
        'pickup_point',
        'dropoff_point',
        'status',
        'base_price',
        'surge_multiplier',
        'total_price',
        'started_at',
        'completed_at',
        'distance_km',
        'correlation_id',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'pickup_point' => 'json',
        'dropoff_point' => 'json',
        'tags' => 'collection',
        'metadata' => 'json',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'base_price' => 'integer',
        'total_price' => 'integer',
        'surge_multiplier' => 'float',
        'distance_km' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($query) => $query->where('tenant_id', tenant('id') ?? 0));
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(related: \App\Models\User::class, foreignKey: 'passenger_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(TaxiVehicle::class, 'vehicle_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\TaxiRideFactory::new();
    }
}
