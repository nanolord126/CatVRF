<?php

declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

/**
 * КАНОН 2026: Модель Vehicle (Транспортное средство).
 * Слой 2: Доменные модели.
 */
final class Vehicle extends Model
{
    use SoftDeletes, LogsActivity;

    protected $table = 'taxi_vehicles';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'driver_id',
        'brand',
        'model',
        'plate_number',
        'color',
        'year',
        'class',
        'documents',
        'status',
        'correlation_id',
        'tags'
    ];

    protected $casts = [
        'documents' => 'json',
        'tags' => 'json',
        'year' => 'integer',
        'tenant_id' => 'integer',
        'driver_id' => 'integer'
    ];

    /**
     * Глобальный скоупинг тенанта.
     */
    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle) {
            $vehicle->uuid = $vehicle->uuid ?? (string) Str::uuid();
            $vehicle->tenant_id = $vehicle->tenant_id ?? (tenant()->id ?? 1);
            $vehicle->correlation_id = $vehicle->correlation_id ?? request()->header('X-Correlation-ID');
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
            ->logOnly(['status', 'driver_id', 'documents'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setLogName('fleet_management');
    }

    /**
     * Отношения.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Проверка на подписку (бизнес-логика).
     */
    public function isAllowedForClass(string $requestedClass): bool
    {
        static $classes = [
            'economy' => 1,
            'comfort' => 2,
            'business' => 3,
            'delivery' => 0
        ];

        return ($classes[$this->class] ?? 0) >= ($classes[$requestedClass] ?? 0);
    }
}
