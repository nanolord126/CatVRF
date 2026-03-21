<?php

declare(strict_types=1);

namespace Modules\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель поездки в такси.
 * Согласно КАНОН 2026: полный tracking поездки, GPS маршрут, финансовые данные, surge pricing.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $driver_id
 * @property int|null $passenger_id
 * @property int|null $vehicle_id
 * @property int|null $payment_id
 * @property string|null $uuid
 * @property float $pickup_latitude Широта места подъёма
 * @property float $pickup_longitude Долгота места подъёма
 * @property float $dropoff_latitude Широта пункта назначения
 * @property float $dropoff_longitude Долгота пункта назначения
 * @property string|null $pickup_address Адрес подъёма
 * @property string|null $dropoff_address Адрес доставки
 * @property int $distance_meters Расстояние в метрах
 * @property int $duration_seconds Длительность в секундах
 * @property int $base_price_kopeki Базовая цена в копейках
 * @property int $final_price_kopeki Финальная цена в копейках (с surge)
 * @property float $surge_multiplier Применённый коэффициент surge
 * @property string $status (requested, accepted, started, completed, cancelled)
 * @property \Carbon\Carbon|null $requested_at Время заказа
 * @property \Carbon\Carbon|null $accepted_at Время принятия водителем
 * @property \Carbon\Carbon|null $started_at Время начала поездки
 * @property \Carbon\Carbon|null $completed_at Время завершения
 * @property string|null $cancellation_reason Причина отмены
 * @property int|null $passenger_rating Рейтинг пассажира (1-5)
 * @property int|null $driver_rating Рейтинг водителя (1-5)
 * @property string|null $correlation_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
final class TaxiRide extends Model
{
    use SoftDeletes;

    protected $table = 'taxi_rides';

    protected $fillable = [
        'tenant_id',
        'driver_id',
        'passenger_id',
        'vehicle_id',
        'payment_id',
        'uuid',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
        'pickup_address',
        'dropoff_address',
        'distance_meters',
        'duration_seconds',
        'base_price_kopeki',
        'final_price_kopeki',
        'surge_multiplier',
        'status',
        'requested_at',
        'accepted_at',
        'started_at',
        'completed_at',
        'cancellation_reason',
        'passenger_rating',
        'driver_rating',
        'correlation_id',
        'metadata',
    ];

    protected $casts = [
        'pickup_latitude' => 'float',
        'pickup_longitude' => 'float',
        'dropoff_latitude' => 'float',
        'dropoff_longitude' => 'float',
        'distance_meters' => 'integer',
        'duration_seconds' => 'integer',
        'base_price_kopeki' => 'integer',
        'final_price_kopeki' => 'integer',
        'surge_multiplier' => 'float',
        'passenger_rating' => 'integer',
        'driver_rating' => 'integer',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    /**
     * Статусы поездки.
     */
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Global scope для tenant scoping.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });
    }

    /**
     * Получить водителя.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(\Modules\Taxi\Models\TaxiDriver::class);
    }

    /**
     * Получить пассажира.
     */
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'passenger_id');
    }

    /**
     * Получить транспортное средство.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\Modules\Taxi\Models\TaxiVehicle::class);
    }

    /**
     * Получить финальную цену в рублях.
     */
    public function getFinalPriceInRubles(): float
    {
        return $this->final_price_kopeki / 100;
    }

    /**
     * Получить базовую цену в рублях.
     */
    public function getBasePriceInRubles(): float
    {
        return $this->base_price_kopeki / 100;
    }

    /**
     * Получить длительность в минутах.
     */
    public function getDurationInMinutes(): int
    {
        return (int) ceil($this->duration_seconds / 60);
    }

    /**
     * Получить расстояние в километрах.
     */
    public function getDistanceInKilometers(): float
    {
        return $this->distance_meters / 1000;
    }

    /**
     * Проверить, завершена ли поездка.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Помечить как принятую.
     */
    public function markAsAccepted(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Помечить как начатую.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => self::STATUS_STARTED,
            'started_at' => now(),
        ]);
    }

    /**
     * Помечить как завершённую.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Отменить поездку.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancellation_reason' => $reason,
            'completed_at' => now(),
        ]);
    }

    /**
     * Установить рейтинг пассажира.
     */
    public function setPassengerRating(int $rating): void
    {
        if ($rating >= 1 && $rating <= 5) {
            $this->update(['passenger_rating' => $rating]);
        }
    }

    /**
     * Установить рейтинг водителя.
     */
    public function setDriverRating(int $rating): void
    {
        if ($rating >= 1 && $rating <= 5) {
            $this->update(['driver_rating' => $rating]);
        }
    }
}
