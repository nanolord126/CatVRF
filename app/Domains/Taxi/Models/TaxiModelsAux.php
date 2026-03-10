<?php

namespace App\Domains\Taxi\Models;

use App\Models\AuditLog;
use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Смена водителя с отслеживанием доходов и времени работы
 * 
 * @property int $id
 * @property int $driver_id
 * @property \DateTime $started_at
 * @property \DateTime|null $ended_at
 * @property decimal $total_earnings
 * @property decimal $platform_commission
 * @property decimal $driver_profit
 * @property int $rides_count
 * @property bool $is_active
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class TaxiShift extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_earnings' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'driver_profit' => 'decimal:2',
        'rides_count' => 'integer',
        'is_active' => 'boolean',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(TaxiRide::class, 'shift_id');
    }

    /**
     * Расчёт продолжительности смены в часах
     */
    public function getDurationHours(): float
    {
        $startTime = $this->started_at->timestamp ?? 0;
        $endTime = ($this->ended_at?->timestamp ?? now()->timestamp);
        $secondsDiff = max(0, $endTime - $startTime);
        return $secondsDiff / 3600.0;
    }

    /**
     * Расчёт средней стоимости за поездку
     */
    public function getAverageRidePrice(): float
    {
        if ($this->rides_count === 0) {
            return 0.0;
        }
        return round((float)$this->total_earnings / $this->rides_count, 2);
    }

    /**
     * Расчёт почасового заработка
     */
    public function getHourlyRate(): float
    {
        $hours = $this->getDurationHours();
        if ($hours === 0.0) {
            return 0.0;
        }
        return round((float)$this->driver_profit / $hours, 2);
    }

    /**
     * Завершить смену (если она ещё активна)
     */
    public function complete(): bool
    {
        try {
            if (!$this->is_active) {
                throw new \Exception("Shift is already completed");
            }

            $this->update([
                'ended_at' => now(),
                'is_active' => false
            ]);

            // Логирование завершения смены
            Log::channel('taxi')->info('Shift completed', [
                'shift_id' => $this->id,
                'driver_id' => $this->driver_id,
                'duration_hours' => $this->getDurationHours(),
                'total_earnings' => $this->total_earnings,
                'rides_count' => $this->rides_count
            ]);

            // Audit log
            AuditLog::create([
                'user_id' => $this->driver?->user_id,
                'tenant_id' => $this->tenant_id,
                'action' => 'shift_completed',
                'model' => self::class,
                'model_id' => $this->id,
                'changes' => [
                    'is_active' => [true, false],
                    'ended_at' => [null, now()->toIso8601String()]
                ],
                'correlation_id' => request()?->header('X-Correlation-ID'),
                'ip_address' => request()?->ip()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to complete shift', [
                'shift_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Активные смены (которые сейчас идут)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('ended_at');
    }

    /**
     * Смены за период
     */
    public function scopeForPeriod($query, Carbon $from, Carbon $to)
    {
        return $query->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to);
    }

    /**
     * Смены конкретного водителя
     */
    public function scopeByDriver($query, int $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Вычисляемая длительность смены
     */
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->getDurationHours() . ' ч.';
            }
        );
    }

    /**
     * Вычисляемый статус смены
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->is_active ? 'Активна' : 'Завершена';
            }
        );
    }

    /**
     * Вычисляемая оценка производительности
     */
    protected function efficiency(): Attribute
    {
        return Attribute::make(
            get: function () {
                $hours = $this->getDurationHours();
                if ($hours === 0) {
                    return 'N/A';
                }
                $ridesPerHour = $this->rides_count / $hours;
                return match (true) {
                    $ridesPerHour >= 3.5 => 'Высокая',
                    $ridesPerHour >= 2.5 => 'Хорошая',
                    $ridesPerHour >= 1.5 => 'Средняя',
                    default => 'Низкая'
                };
            }
        );
    }
}

/**
 * Логирование статусов поездки для аудита
 * 
 * @property int $id
 * @property int $ride_id
 * @property string $status
 * @property \DateTime $recorded_at
 * @property array $meta
 * @property \DateTime $created_at
 */
class TaxiRideStatusLog extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function ride(): BelongsTo
    {
        return $this->belongsTo(TaxiRide::class);
    }

    /**
     * Статусы, которые изменяют финансовое состояние
     */
    public const FINANCIAL_STATUSES = [
        'payment_confirmed',
        'payment_failed',
        'refunded'
    ];

    /**
     * Основные статусы жизненного цикла поездки
     */
    public const LIFECYCLE_STATUSES = [
        'searching' => 'Поиск водителя',
        'accepted' => 'Водитель принял',
        'arrived' => 'Водитель прибыл',
        'started' => 'Поездка началась',
        'completed' => 'Поездка завершена',
        'cancelled' => 'Отменено',
        'no_show' => 'Не явился',
    ];

    /**
     * Создать лог статуса с дополнительными данными
     */
    public static function recordStatus(TaxiRide $ride, string $status, array $meta = []): self
    {
        try {
            $log = self::create([
                'ride_id' => $ride->id,
                'status' => $status,
                'recorded_at' => now(),
                'meta' => array_merge([
                    'previous_status' => $ride->status,
                    'driver_location' => $ride->driver?->last_location,
                    'distance_to_destination' => $meta['distance'] ?? null,
                    'eta_minutes' => $meta['eta'] ?? null,
                ], $meta)
            ]);

            Log::channel('taxi')->info('Ride status recorded', [
                'ride_id' => $ride->id,
                'status' => $status,
                'driver_id' => $ride->driver_id,
                'meta' => $meta
            ]);

            return $log;
        } catch (\Exception $e) {
            Log::channel('taxi')->error('Failed to record ride status', [
                'ride_id' => $ride->id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Все логи для поездки в хронологическом порядке
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('recorded_at', 'asc');
    }

    /**
     * Только финансово значимые события
     */
    public function scopeFinancial($query)
    {
        return $query->whereIn('status', self::FINANCIAL_STATUSES);
    }

    /**
     * Вычисляемое читаемое имя статуса
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                return self::LIFECYCLE_STATUSES[$this->status] ?? $this->status;
            }
        );
    }
}
