<?php

declare(strict_types=1);

namespace App\Domains\Auto\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Модель Поездки (Такси/Грузоперевозки)
 */
final class TaxiRide extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $table = 'taxi_rides';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'passenger_id',
        'driver_id',
        'vehicle_id',
        'pickup_address',
        'dest_address',
        'pickup_location', // PostGIS Point
        'dest_location',   // PostGIS Point
        'status',          // pending, accepted, on_way, arrived, started, completed, cancelled
        'price_cents',
        'distance_meters',
        'duration_seconds',
        'surge_multiplier',
        'cargo_type',      // passenger, express, cargo, oversized
        'cargo_weight_kg',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'pickup_location' => 'string', // Требует кастомный каст для PostGIS или обработки в Service
        'dest_location' => 'string',
        'price_cents' => 'integer',
        'distance_meters' => 'integer',
        'duration_seconds' => 'integer',
        'surge_multiplier' => 'float',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? request()->header('X-Correlation-ID', (string) Str::uuid());
            $model->surge_multiplier = $model->surge_multiplier ?? 1.0;
        });
    }

    public function passenger(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'passenger_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, 'driver_id');
    }
}
