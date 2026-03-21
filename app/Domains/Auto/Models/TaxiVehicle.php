<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Автомобиль такси.
 * Production 2026.
 */
final class TaxiVehicle extends Model
{
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
