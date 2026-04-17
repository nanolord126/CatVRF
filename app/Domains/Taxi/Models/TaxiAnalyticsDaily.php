<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class TaxiAnalyticsDaily extends Model
{
    use HasFactory;

    protected $table = 'taxi_analytics_daily';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'date',
        'total_rides',
        'completed_rides',
        'cancelled_rides',
        'total_revenue_kopeki',
        'total_distance_km',
        'total_duration_minutes',
        'average_ride_distance_km',
        'average_ride_duration_minutes',
        'average_ride_price_rubles',
        'surge_multiplier_avg',
        'active_drivers_count',
        'new_drivers_count',
        'active_passengers_count',
        'new_passengers_count',
        'peak_hour_rides',
        'peak_hour',
        'b2b_rides_count',
        'b2c_rides_count',
        'fleet_rides_count',
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
        'average_ride_distance_km' => 'float',
        'average_ride_duration_minutes' => 'float',
        'average_ride_price_rubles' => 'float',
        'surge_multiplier_avg' => 'float',
        'active_drivers_count' => 'integer',
        'new_drivers_count' => 'integer',
        'active_passengers_count' => 'integer',
        'new_passengers_count' => 'integer',
        'peak_hour_rides' => 'integer',
        'peak_hour' => 'integer',
        'b2b_rides_count' => 'integer',
        'b2c_rides_count' => 'integer',
        'fleet_rides_count' => 'integer',
        'metadata' => 'json',
    ];

    protected $hidden = ['metadata'];

    protected static function booted(): void
    {
        static::creating(function (TaxiAnalyticsDaily $analytics) {
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
     * Получить общую выручку в рублях.
     */
    public function getTotalRevenueInRubles(): float
    {
        return $this->total_revenue_kopeki / 100;
    }

    /**
     * Получить процент завершенных поездок.
     */
    public function getCompletionRate(): float
    {
        if ($this->total_rides === 0) {
            return 0.0;
        }

        return ($this->completed_rides / $this->total_rides) * 100;
    }

    /**
     * Получить процент отмененных поездок.
     */
    public function getCancellationRate(): float
    {
        if ($this->total_rides === 0) {
            return 0.0;
        }

        return ($this->cancelled_rides / $this->total_rides) * 100;
    }
}
