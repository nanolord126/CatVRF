<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiDriverAnalytics extends Model
{
    use HasFactory;

    protected $table = 'taxi_driver_analytics';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'driver_id',
        'date',
        'total_rides',
        'completed_rides',
        'cancelled_rides',
        'total_revenue_kopeki',
        'total_distance_km',
        'total_duration_minutes',
        'online_minutes',
        'acceptance_rate',
        'cancellation_rate',
        'average_rating',
        'total_tips_kopeki',
        'bonus_kopeki',
        'penalty_kopeki',
        'surge_multiplier_avg',
        'peak_hours_rides',
        'peak_hours_revenue_kopeki',
        'b2b_rides_count',
        'b2b_revenue_kopeki',
        'average_response_time_seconds',
        'average_pickup_time_minutes',
        'correlation_id',
        'metadata'
    ];

    protected $casts = [
        'date' => 'date',
        'total_rides' => 'integer',
        'completed_rides' => 'integer',
        'cancelled_rides' => 'integer',
        'total_revenue_kopeki' => 'integer',
        'total_distance_km' => 'float',
        'total_duration_minutes' => 'integer',
        'online_minutes' => 'integer',
        'acceptance_rate' => 'float',
        'cancellation_rate' => 'float',
        'average_rating' => 'float',
        'total_tips_kopeki' => 'integer',
        'bonus_kopeki' => 'integer',
        'penalty_kopeki' => 'integer',
        'surge_multiplier_avg' => 'float',
        'peak_hours_rides' => 'integer',
        'peak_hours_revenue_kopeki' => 'integer',
        'b2b_rides_count' => 'integer',
        'b2b_revenue_kopeki' => 'integer',
        'average_response_time_seconds' => 'integer',
        'average_pickup_time_minutes' => 'float',
        'metadata' => 'json',
    ];

    protected $hidden = ['metadata'];

    protected static function booted(): void
    {
        static::creating(function (TaxiDriverAnalytics $analytics) {
            $analytics->uuid = $analytics->uuid ?? (string) Str::uuid();
            $analytics->tenant_id = $analytics->tenant_id ?? (tenant()->id ?? 1);
            $analytics->correlation_id = $analytics->correlation_id ?? (request()->header('X-Correlation-ID') ?? (string) Str::uuid());
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
     * Получить общую выручку в рублях.
     */
    public function getTotalRevenueInRubles(): float
    {
        return $this->total_revenue_kopeki / 100;
    }

    /**
     * Получить чаевые в рублях.
     */
    public function getTotalTipsInRubles(): float
    {
        return $this->total_tips_kopeki / 100;
    }

    /**
     * Получить бонус в рублях.
     */
    public function getBonusInRubles(): float
    {
        return $this->bonus_kopeki / 100;
    }

    /**
     * Получить штраф в рублях.
     */
    public function getPenaltyInRubles(): float
    {
        return $this->penalty_kopeki / 100;
    }

    /**
     * Получить чистый доход (выручка + чаевые + бонус - штраф).
     */
    public function getNetIncomeKopeki(): int
    {
        return $this->total_revenue_kopeki + $this->total_tips_kopeki + $this->bonus_kopeki - $this->penalty_kopeki;
    }

    /**
     * Получить чистый доход в рублях.
     */
    public function getNetIncomeInRubles(): float
    {
        return $this->getNetIncomeKopeki() / 100;
    }

    /**
     * Получить средний заработок в час.
     */
    public function getAverageHourlyEarningsRubles(): float
    {
        if ($this->online_minutes === 0) {
            return 0.0;
        }

        return ($this->getNetIncomeInRubles() / $this->online_minutes) * 60;
    }

    /**
     * Получить средний заработок на поездку.
     */
    public function getAverageEarningsPerRideRubles(): float
    {
        if ($this->completed_rides === 0) {
            return 0.0;
        }

        return $this->getNetIncomeInRubles() / $this->completed_rides;
    }

    /**
     * Получить процент онлайн времени.
     */
    public function getOnlineUtilizationRate(): float
    {
        $totalMinutesInDay = 24 * 60;
        if ($totalMinutesInDay === 0) {
            return 0.0;
        }

        return ($this->online_minutes / $totalMinutesInDay) * 100;
    }
}
