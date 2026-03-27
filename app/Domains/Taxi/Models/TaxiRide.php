<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель TaxiRide (Поездка).
 * Слой 2: Доменные модели.
 */
final class TaxiRide extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'taxi_rides';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'passenger_id',
        'driver_id',
        'vehicle_id',
        'status',
        'pickup_address',
        'pickup_point',
        'dropoff_address',
        'dropoff_point',
        'distance_km',
        'base_price',
        'surge_multiplier',
        'total_price',
        'fleet_commission',
        'platform_commission',
        'idempotency_key',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'metadata' => 'json',
        'tags' => 'json',
        'status' => 'string',
        'distance_km' => 'float',
        'base_price' => 'integer',
        'total_price' => 'integer',
        'fleet_commission' => 'integer',
        'platform_commission' => 'integer',
        'surge_multiplier' => 'float',
        'tenant_id' => 'integer',
        'passenger_id' => 'integer',
        'driver_id' => 'integer',
        'vehicle_id' => 'integer'
    ];

    protected $hidden = ['metadata'];

    /**
     * Глобальный скоупинг тенанта.
     */
    protected static function booted(): void
    {
        static::creating(function (TaxiRide $ride) {
            $ride->uuid = $ride->uuid ?? (string) Str::uuid();
            $ride->tenant_id = $ride->tenant_id ?? (tenant()->id ?? 1);
            $ride->status = $ride->status ?? 'pending';
            $ride->correlation_id = $ride->correlation_id ?? request()->header('X-Correlation-ID');
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Настройка логов активности.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'driver_id', 'total_price'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setLogName('ride_events');
    }

    /**
     * Отношения.
     */
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'passenger_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Расчёт времени (бизнес-логика слоев).
     */
    public function estimateArrivalMinutes(): int
    {
        // В реальном сервисе здесь вызов OSRM/Yandex API
        return (int) ($this->distance_km * 3) + 5;
    }
}
