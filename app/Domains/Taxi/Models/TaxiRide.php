<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\User;
use App\Domains\Taxi\Models\Driver;
use App\Domains\Taxi\Models\Vehicle;

final class TaxiRide extends Model
{
    use HasFactory, SoftDeletes;

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
     * Статусы поездки.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Глобальный скоупинг тенанта.
     */
    protected static function booted(): void
    {
        static::creating(function (TaxiRide $ride) {
            $ride->uuid = $ride->uuid ?? (string) Str::uuid();
            $ride->tenant_id = $ride->tenant_id ?? (tenant()->id ?? 1);
            $ride->status = $ride->status ?? self::STATUS_PENDING;
            $ride->correlation_id = $ride->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
        });

        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
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
        return (int) ($this->distance_km * 3) + 5;
    }

    /**
     * Проверить, завершена ли поездка.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Проверить, отменена ли поездка.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Получить финальную цену в рублях.
     */
    public function getFinalPriceInRubles(): float
    {
        return $this->total_price / 100;
    }

    /**
     * Получить базовую цену в рублях.
     */
    public function getBasePriceInRubles(): float
    {
        return $this->base_price / 100;
    }

    /**
     * Рассчитать заработок водителя.
     */
    public function calculateDriverEarnings(): int
    {
        $commission = $this->fleet_commission + $this->platform_commission;
        return $this->total_price - $commission;
    }

    /**
     * Проверить, можно ли отменить поездку.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACCEPTED]);
    }

    /**
     * Проверить, находится ли поездка в процессе.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_STARTED]);
    }

    /**
     * Рассчитать итоговую цену с учётом множителя.
     */
    public function calculateTotalPrice(): int
    {
        return (int) ($this->base_price * $this->surge_multiplier);
    }

    /**
     * Получить продолжительность поездки в минутах.
     */
    public function getDurationMinutes(): int
    {
        return (int) ($this->distance_km * 2.5);
    }

    /**
     * Проверить валидность перехода статуса.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            self::STATUS_PENDING => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED],
            self::STATUS_ACCEPTED => [self::STATUS_STARTED, self::STATUS_CANCELLED],
            self::STATUS_STARTED => [self::STATUS_COMPLETED],
            self::STATUS_CANCELLED => [],
            self::STATUS_COMPLETED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }
}
