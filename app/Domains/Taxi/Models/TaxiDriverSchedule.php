<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiDriverSchedule extends Model
{
    use HasFactory;

    protected $table = 'taxi_driver_schedules';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'driver_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'break_start_time',
        'break_end_time',
        'target_rides',
        'target_earnings_kopeki',
        'actual_rides',
        'actual_earnings_kopeki',
        'online_minutes',
        'notes',
        'correlation_id',
        'metadata',
        'tags'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
        'target_rides' => 'integer',
        'target_earnings_kopeki' => 'integer',
        'actual_rides' => 'integer',
        'actual_earnings_kopeki' => 'integer',
        'online_minutes' => 'integer',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    protected $hidden = ['metadata'];

    /**
     * Статусы смены.
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    protected static function booted(): void
    {
        static::creating(function (TaxiDriverSchedule $schedule) {
            $schedule->uuid = $schedule->uuid ?? (string) Str::uuid();
            $schedule->tenant_id = $schedule->tenant_id ?? (tenant()->id ?? 1);
            $schedule->status = $schedule->status ?? self::STATUS_SCHEDULED;
            $schedule->actual_rides = $schedule->actual_rides ?? 0;
            $schedule->actual_earnings_kopeki = $schedule->actual_earnings_kopeki ?? 0;
            $schedule->online_minutes = $schedule->online_minutes ?? 0;
            $schedule->correlation_id = $schedule->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
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
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    /**
     * Получить целевой заработок в рублях.
     */
    public function getTargetEarningsInRubles(): float
    {
        return $this->target_earnings_kopeki / 100;
    }

    /**
     * Получить фактический заработок в рублях.
     */
    public function getActualEarningsInRubles(): float
    {
        return $this->actual_earnings_kopeki / 100;
    }

    /**
     * Получить процент выполнения целевого заработка.
     */
    public function getEarningsTargetProgress(): float
    {
        if ($this->target_earnings_kopeki === 0) {
            return 0.0;
        }

        return ($this->actual_earnings_kopeki / $this->target_earnings_kopeki) * 100;
    }

    /**
     * Получить процент выполнения целевого количества поездок.
     */
    public function getRidesTargetProgress(): float
    {
        if ($this->target_rides === 0) {
            return 0.0;
        }

        return ($this->actual_rides / $this->target_rides) * 100;
    }

    /**
     * Проверить, активна ли смена.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Проверить, завершена ли смена.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Пометить как активную.
     */
    public function markAsActive(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Пометить как завершенную.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'end_time' => now(),
        ]);
    }

    /**
     * Отменить смену.
     */
    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => array_merge($this->metadata ?? [], ['cancellation_reason' => $reason]),
        ]);
    }
}
