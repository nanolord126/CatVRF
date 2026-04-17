<?php declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TaxiDriverStats extends Model
{
    use HasFactory;

    protected $table = 'taxi_driver_stats';

    protected $fillable = [
        'driver_id',
        'tenant_id',
        'business_group_id',
        'rides_started',
        'rides_completed',
        'rides_cancelled',
        'total_earnings',
        'total_distance_km',
        'total_time_minutes',
        'average_rating',
        'current_streak',
        'max_streak',
        'last_ride_at',
        'online_hours_today',
        'online_hours_week',
        'rides_today',
        'rides_week',
        'earnings_today',
        'earnings_week',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'driver_id' => 'integer',
        'tenant_id' => 'integer',
        'business_group_id' => 'integer',
        'rides_started' => 'integer',
        'rides_completed' => 'integer',
        'rides_cancelled' => 'integer',
        'total_earnings' => 'integer',
        'total_distance_km' => 'float',
        'total_time_minutes' => 'integer',
        'average_rating' => 'float',
        'current_streak' => 'integer',
        'max_streak' => 'integer',
        'last_ride_at' => 'datetime',
        'online_hours_today' => 'float',
        'online_hours_week' => 'float',
        'rides_today' => 'integer',
        'rides_week' => 'integer',
        'earnings_today' => 'integer',
        'earnings_week' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'correlation_id',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(TaxiDriver::class, 'driver_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }
}
